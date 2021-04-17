<?php

namespace Flynsarmy\ContentBlocks;

use Backend\Widgets\Form;
use Event;
use System\Classes\PluginBase;
use Cms\Classes\Content as CmsContent;
use Cms\Classes\Theme as CmsTheme;

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
         * Register menu items for the Winter.Pages plugin
         */
        Event::listen('backend.form.extendFields', function (Form $widget) {
            if (!$widget->getController() instanceof \CMS\Controllers\Index) {
                return;
            }

            if (!$widget->model instanceof \Cms\Classes\Layout &&
                !$widget->model instanceof \Cms\Classes\Page &&
                !$widget->model instanceof \Cms\Classes\Partial) {
                return;
            }

            $theme = CmsTheme::getActiveTheme();

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
                    'type' => $this->getBlockFieldType($blockName),
                    'tab' => 'Content',
                ];

                // Get the HTML of the content block
                $content = CmsContent::load($theme, $blocks['name'][$i]);
                if ($content) {
                    $content = json_decode($content, true);
                    if ($content) {
                        $content = $content['content'];
                    }
                }

                // Add the data to the model
                $widget->model->settings['content_blocks'][$blockName] = $content;
            }

            $widget->addFields($fields, 'primary');
        });
    }

    public function getBlockFieldType(string $filename): string
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
}
