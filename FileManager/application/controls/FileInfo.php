<?php

namespace Ixtrum\FileManager\Application\Controls;

class FileInfo extends \Ixtrum\FileManager
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/FileInfo.latte");
        $this->template->setTranslator($this->context->translator);
        $this->template->thumbDir = $this->context->parameters["resDir"] . "img/icons/large/";

        if (count($this->selectedFiles) > 1) {
            $this->template->files = $this->context->filesystem->filesInfo(
                    $this->getActualDir(), $this->selectedFiles, true
            );
        } elseif (isset($this->selectedFiles[0])) {
            $this->template->file = $this->context->filesystem->fileInfo(
                    $this->getActualDir(), $this->selectedFiles[0]
            );
        }

        $this->template->render();
    }

}