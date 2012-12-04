<?php

namespace Ixtrum\FileManager\Application\Controls;

class DiskUsage extends \Ixtrum\FileManager
{

    public function render()
    {
        $this->template->setFile(__DIR__ . '/DiskUsage.latte');
        $this->template->setTranslator($this->system->translator);
        $this->template->sizeinfo = $this->getDiskInfo();
        $this->template->render();
    }

}