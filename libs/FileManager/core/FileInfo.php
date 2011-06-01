<?php

use Nette\Environment;

class FileInfo extends FileManager
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
        $template->setFile(__DIR__ . '/FileInfo.latte');
        $template->setTranslator(parent::getParent()->getTranslator());
        $template->fileinfo = $this['files']->fileDetails($actualdir, $this->filename);
        $template->config = $this->config;
        $template->actualdir = $actualdir;
        $template->render();
    }
}