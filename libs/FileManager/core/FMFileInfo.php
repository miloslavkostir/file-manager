<?php

use Nette\Environment;

class FMFileInfo extends FileManager
{
    /** @var array */
    public $config;

    /** @var string */
    public $filename;

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;

        $template = $this->template;
        $template->setFile(__DIR__ . '/FMFileInfo.latte');

        // set language
        $lang_file = __DIR__ . '/../locale/FileManager.'. $this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");


        $template->fileinfo = $this['fmFiles']->fileDetails($actualdir, $this->filename);
        $template->config = $this->config;
        $template->actualdir = $actualdir;

        $template->render();
    }
}