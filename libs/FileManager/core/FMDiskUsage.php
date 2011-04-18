<?php

class FMDiskUsage extends FileManager
{
    /** @var array */
    public $config;

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/FMDiskUsage.latte');

        // set language
        $lang_file = __DIR__ . '/../locale/FileManager.'. $this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");

        $template->sizeinfo = $this['tools']->diskSizeInfo();

        $template->render();
    }
}