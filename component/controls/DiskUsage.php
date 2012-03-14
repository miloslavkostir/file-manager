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
                $template->sizeinfo = $this->context->tools->diskSizeInfo();
                $template->render();
        }
}