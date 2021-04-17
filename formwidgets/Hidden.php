<?php

namespace Flynsarmy\ContentBlocks\FormWidgets;

use Backend\Classes\FormWidgetBase;

class Hidden extends FormWidgetBase
{
    /**
     * Render the form widget
     */
    public function render()
    {
        $this->prepareVars();
        return $this->makePartial('hidden');
    }

    private function defaultVars()
    {
        return [
            'fieldName' => $this->fieldName,
        ];
    }

    /**
     * Prepare widget variables
     */
    public function prepareVars()
    {

        $defaultConfig = $this->defaultVars();
        // Retrieve only config options relevant to this plugin
        $config = array_only((array)$this->config, array_keys($defaultConfig));
        // Apply defaults
        $config = array_merge($defaultConfig, $config);

        // Javascript configuration
        $this->vars['config'] = $config;
        $this->vars['field'] = $this->formField;
    }
}
