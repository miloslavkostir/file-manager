<?php

namespace Ixtrum\FileManager\Controls;

class FileInfo extends \Ixtrum\FileManager
{

    /** @var string */
    public $filename;

    public function __construct($userConfig)
    {
        parent::__construct($userConfig);
    }

    public function render()
    {
        $actualdir = $this->context->application->getActualDir();

        $template = $this->template;
        $template->setFile(__DIR__ . '/FileInfo.latte');
        $template->setTranslator($this->context->translator);
        $template->fileinfo = $this->context->filesystem->fileDetails($actualdir, $this->filename);
        $template->render();
    }

}