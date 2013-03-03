<?php

namespace Ixtrum\FileManager\Application\Controls;

class DiskUsage extends \Ixtrum\FileManager\Application\Controls
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/DiskUsage.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->spaceLeft = $this->getFreeSpace();

        if ($this->system->parameters["quota"]) {

            $this->template->usedSize = $this->system->filesystem->getSize($this->system->parameters["uploadroot"]);
            $this->template->usedPercent = round(($this->template->usedSize / ($this->system->parameters["quotaLimit"] * 1048576)) * 100);
        } else {

            $total = disk_total_space($this->system->parameters["uploadroot"]);
            $used = $total - $this->template->spaceLeft;
            $this->template->usedSize = $used;
            $this->template->usedPercent = round(($used / $total ) * 100);
        }

        $this->template->render();
    }

}