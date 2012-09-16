<?php

namespace Ixtrum\FileManager\Controls;

class DiskUsage extends \Ixtrum\FileManager
{

    public function render()
    {
        $this->template->setFile(__DIR__ . '/DiskUsage.latte');
        $this->template->setTranslator($this->context->translator);
        $this->template->sizeinfo = $this->context->filesystem->diskSizeInfo();
        $this->template->render();
    }

}