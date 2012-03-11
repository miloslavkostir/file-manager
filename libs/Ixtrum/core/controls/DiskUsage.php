<?php

namespace Ixtrum;

class DiskUsage extends FileManager
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
                $template->sizeinfo = $this->context->tools->diskSizeInfo();
                $template->render();
        }
}