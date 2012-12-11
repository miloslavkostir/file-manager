<?php

namespace Ixtrum\FileManager\Application\Controls;

class FileInfo extends \Ixtrum\FileManager\Application\Controls
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/FileInfo.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->thumbDir = $this->system->parameters["resDir"] . "img/icons/large/";

        if (count($this->selectedFiles) > 1) {
            $this->template->files = $this->system->filesystem->filesInfo(
                    $this->getActualDir(), $this->selectedFiles, true
            );
        } elseif (isset($this->selectedFiles[0])) {
            $this->template->file = $this->system->filesystem->fileInfo(
                    $this->getActualDir(), $this->selectedFiles[0]
            );
        }

        $this->template->render();
    }

}