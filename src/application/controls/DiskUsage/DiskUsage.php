<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Application\Controls;

/**
 * Disk usage control.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class DiskUsage extends \Ixtrum\FileManager\Application\Controls
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/DiskUsage.latte");
        $this->template->setTranslator($this->system->getService("translator"));
        $this->template->spaceLeft = $this->getFreeSpace();

        if ($this->system->parameters["quota"]) {

            $this->template->usedSize = $this->system->getService('filesystem')->getSize($this->system->parameters["dataDir"]);
            $this->template->usedPercent = round(($this->template->usedSize / ($this->system->parameters["quotaLimit"] * 1048576)) * 100);
        } else {

            $total = disk_total_space($this->system->parameters["dataDir"]);
            $used = $total - $this->template->spaceLeft;
            $this->template->usedSize = $used;
            $this->template->usedPercent = round(($used / $total ) * 100);
        }

        $this->template->render();
    }

}