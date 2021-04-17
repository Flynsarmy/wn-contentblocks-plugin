<?php

namespace Flynsarmy\ContentBlocks;

use Backend\Widgets\Form;
use Cms\Classes\CmsCompoundObject;
use Cms\Classes\Content as CmsContent;
use Cms\Classes\Theme as CmsTheme;
use Cms\Classes\Layout as CmsLayout;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Partial as CmsPartial;
use Cms\Controllers\Index as CmsController;
use Event;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Content Blocks',
            'description' => 'Allow clients to update content blocks directly from the layouts/pages/partials that use them.',
            'author'      => 'Flynsarmy',
            'icon'        => 'icon-code',
            'homepage'    => 'https://github.com/Flynsarmy/wn-contentblocks-plugin',
        ];
    }

    public function boot()
    {
        /*
         * Update content blocks
         */
        Event::listen('cms.template.processSettingsBeforeSave', function (CmsController $controller, object &$dataHolder) {
            // Unset the content block data so it doesn't save to the model
            if (isset($dataHolder->settings['content_blocks'])) {
                unset($dataHolder->settings['content_blocks']);
            }
        });

        /*
         * Add content blocks to the 'Content' tab
         */
        Event::listen('backend.form.extendFields', function (Form $widget) {
            if (!$widget->getController() instanceof CmsController) {
                return;
            }

            if (!$widget->model instanceof CmsLayout &&
                !$widget->model instanceof CmsPage &&
                !$widget->model instanceof CmsPartial) {
                return;
            }

            // Current layout/page/partial markup
            $markup = $widget->model->getTwigContent();
            // Grab all content blocks and their names
            preg_match_all("/{%\s+content\s+['\"](?P<name>[^'\"]+)['\"]\s+.*%}/", $markup, $blocks);

            if (!count($blocks[0])) {
                return;
            }

            $fields = [];
            foreach ($blocks[0] as $i => $block) {
                $blockName = $blocks['name'][$i];

                // Add the field to the form
                $fields["settings[content_blocks][$blockName]"] = [
                    'label' => $blocks['name'][$i],
                    'type' => $this->getFormFieldTypeForBlock($blockName),
                    'comment' => $this->getFormFieldCommentForBlock($block),
                    'tab' => 'Content',
                ];

                // Add the content block to the model
                $widget->model->settings['content_blocks'][$blockName] =
                    $this->getContentBlock($blockName);
            }

            $widget->addFields($fields, 'primary');
        });
    }

    /**
     * Returns the contents of the content block with given filename or empty
     * string if the content block doesn't exist.
     *
     * @param string $filename
     * @return string
     */
    public function getContentBlock(string $filename): string
    {
        $content = json_decode(
            CmsContent::load(CmsTheme::getActiveTheme(), $filename),
            true
        );

        if (!$content) {
            return '';
        }

        return $content['content'];
    }

    /**
     * Returns the form field type for given block.
     *
     * @param string $filename
     * @return string
     */
    public function getFormFieldTypeForBlock(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        switch ($ext) {
            case 'txt':
                return 'textarea';
            case 'md':
                return 'markdown';
            default:
                return 'richeditor';
        }
    }

    public function getFormFieldCommentForBlock(string $block): string
    {
        // Grab all argument names passed to this block
        preg_match_all("/\s(?P<args>[a-zA-Z]+)=/", $block, $args);
        $args = $args['args'];

        if (!count($args)) {
            return "";
        }

        return "This block may use the following variables: {{ " . implode(' }}, {{', $args) . ' }}';
    }
}
