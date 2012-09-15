<?php

namespace Ixtrum\FileManager\Controls;

class FileInfo extends \Ixtrum\FileManager
{

    /** @var string */
    public $filename;

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . "/FileInfo.latte");
        $template->setTranslator($this->context->translator);
        $template->fileinfo = $this->context->filesystem->fileDetails(
                $this->context->session->get("actualdir"), $this->filename
        );
        $template->render();
    }

}