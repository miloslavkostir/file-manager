<?php

namespace Ixtrum\FileManager\Controls;

class DiskUsage extends \Ixtrum\FileManager
{

    public function __construct($userConfig)
    {
        parent::__construct($userConfig);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/DiskUsage.latte');
        $template->setTranslator($this->context->translator);
        $template->sizeinfo = $this->context->filesystem->diskSizeInfo();
        $template->render();
    }

}