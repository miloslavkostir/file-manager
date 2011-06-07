<?php

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
        $actualdir = $this['system']->getActualDir();

        $template = $this->template;
        $template->setFile(__DIR__ . '/FileInfo.latte');
        $template->setTranslator(parent::getParent()->getTranslator());
        $template->fileinfo = $this['files']->fileDetails($actualdir, $this->filename);
        $template->render();
    }
}