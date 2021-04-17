<?php

namespace Flynsarmy\ContentBlocks;

use ApplicationException;
use Backend\Classes\Controller as BackendController;
use Backend\Widgets\Form;
use Cms\Classes\Content as CmsContent;
use Cms\Classes\Theme as CmsTheme;
use Cms\Classes\Layout as CmsLayout;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Partial as CmsPartial;
use Cms\Controllers\Index as CmsController;
use Event;
use Lang;
use Request;
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
            if (!isset($dataHolder->settings['content_blocks'])) {
                return;
            }

            // Do the actual updating
            $this->saveContentBlocks(
                $dataHolder->settings['content_blocks_dictionary'],
                $dataHolder->settings['content_blocks']
            );

            // Unset the content block data so it doesn't save to the model
            unset($dataHolder->settings['content_blocks']);
            unset($dataHolder->settings['content_blocks_dictionary']);
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
            // Block names (filepaths) can include / characters which aren't
            // allowed in HTML ID attributes, so instead use an md5 of the name
            // and add a hidden dictionary field which we'll use later to
            // figure out which md5 belongs to which content block.
            $dictionary = [];
            foreach ($blocks[0] as $i => $block) {
                $blockName = $blocks['name'][$i];
                $blockId = md5($blockName);
                $dictionary[$blockId] = $blockName;

                // Add the field to the form
                $fields["settings[content_blocks][$blockId]"] = [
                    'label' => $blocks['name'][$i],
                    'type' => $this->getFormFieldTypeForBlock($blockName),
                    'comment' => $this->getFormFieldCommentForBlock($block),
                    'tab' => 'Content',
                ];

                // Add the content block to the model
                $widget->model->settings['content_blocks'][$blockId] =
                    $this->getContentBlock($blockName);
            }

            $widget->model->settings['content_blocks_dictionary'] = json_encode($dictionary);

            $fields["settings[content_blocks_dictionary]"] = [
                'type' => 'Flynsarmy\ContentBlocks\FormWidgets\Hidden',
                'tab' => 'Content',
            ];

            $widget->addFields($fields, 'primary');
        });

        /*
         * Reload the content block fields on save
         */
    //     Event::listen('backend.ajax.beforeRunHandler', function (BackendController $controller, string $handler) {
    //         if (!$controller instanceof CmsController) {
    //             return;
    //         }

    //         if ($handler != 'onSave') {
    //             return;
    //         }

    //         $type = Request::input('templateType');

    //         // We only want to mess with the layout/page/partial forms
    //         if (!in_array($type, ['page', 'partial', 'layout'])) {
    //             return;
    //         }

    //         // Get the default result
    //         $result = call_user_func_array(
    //             [$controller, $handler],
    //             array_values(\Backend\Classes\BackendController::$params)
    //         );

    //         // Some boilerplate required to get a new form widget
    //         $alias = Request::input('formWidgetAlias');
    //         $template = $this->loadTemplate($type, Request::input('templatePath'));

    //         // makeTemplateFormWidget is protected, so recreate the code
    //         $formConfigs = [
    //             'page'    => '~/modules/cms/classes/page/fields.yaml',
    //             'partial' => '~/modules/cms/classes/partial/fields.yaml',
    //             'layout'  => '~/modules/cms/classes/layout/fields.yaml',
    //         ];
    //         $widgetConfig = $controller->makeConfig($formConfigs[$type]);
    //         $widgetConfig->model = $template;
    //         $widgetConfig->alias = $alias ?:
    //             'form'.studly_case($type).md5($template->exists ? $template->getFileName() : uniqid());
    //         $form = $controller->makeWidget('Backend\Widgets\Form', $widgetConfig);
    //         $form->bindToController();

    //         foreach ($form->getFields() as $field) {
    //             // We're only interested in our content block fields
    //             if (strpos($field->fieldName, 'settings[content_blocks]') !== 0) {
    //                 continue;
    //             }

    //             $result['#'.$field->getId()] = $form->renderField($field->fieldName, ['useContainer' => true]);
    //         };

    //         return $result;
    //     });
    // }

    // public function registerFormWidgets()
    // {
    //     return [
    //         'Flynsarmy\ContentBlocks\FormWidgets\Hidden' => [
    //             'label' => 'TagBox',
    //             'alias' => 'contentblocks-hidden'
    //         ],
    //     ];
    // }

    /**
     * Returns the contents of the content block with given filename or empty
     * string if the content block doesn't exist.
     *
     * @param string $filename
     * @return string
     */
    public function getContentBlock(string $filename): string
    {
        $model = CmsContent::load(CmsTheme::getActiveTheme(), $filename);

        return $model ? $model->content : '';
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

    /**
     * Displays the list of block arguments available if any were passed.
     *
     * @param string $block
     * @return string
     */
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

    /**
     * Saves the content block data supplied by our form into the respective
     * CMS content blocks.
     *
     * @param string $dictionary
     * @param array $blocks
     * @return void
     */
    public function saveContentBlocks(string $dictionary, array $blocks): void
    {
        // Array of md5 => fileName
        $dictionary = json_decode($dictionary, true);

        foreach ($blocks as $fileId => $markup) {
            $fileName = $dictionary[$fileId];
            // Load the content block
            $model = CmsContent::load(CmsTheme::getActiveTheme(), $fileName);

            // If it doesn't exist, create it
            if (!$model) {
                $model = new CmsContent([
                    'fileName' => $fileName,
                ]);
            }

            $model->markup = $markup;
            $model->save();
        }
    }

    // /**
    //  * Resolves a template type to its class name. Taken from CmsController.
    //  * @param string $type
    //  * @return string
    //  */
    // protected function resolveTypeClassName($type)
    // {
    //     $types = [
    //         'page'    => CmsPage::class,
    //         'partial' => CmsPartial::class,
    //         'layout'  => CmsLayout::class,
    //     ];

    //     if (!array_key_exists($type, $types)) {
    //         throw new ApplicationException(Lang::get('cms::lang.template.invalid_type'));
    //     }

    //     return $types[$type];
    // }

    // /**
    //  * Returns an existing template of a given type. Taken from CmsController.
    //  * @param string $type
    //  * @param string $path
    //  * @return mixed
    //  */
    // protected function loadTemplate($type, $path)
    // {
    //     $class = $this->resolveTypeClassName($type);

    //     if (!($template = call_user_func([$class, 'load'], CmsTheme::getActiveTheme(), $path))) {
    //         throw new ApplicationException(Lang::get('cms::lang.template.not_found'));
    //     }

    //     return $template;
    // }
}
