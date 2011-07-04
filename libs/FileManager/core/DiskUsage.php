<?php

class DiskUsage extends FileManager
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
        $template->setFile(__DIR__ . '/DiskUsage.latte');
        $template->setTranslator($this['system']->getTranslator());
        $template->sizeinfo = $this['tools']->diskSizeInfo();
        $template->render();
    }
}