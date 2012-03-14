<?php

namespace Ixtrum\FileManager\Controls;;

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
                $actualdir = $this->context->system->getActualDir();

                $template = $this->template;
                $template->setFile(__DIR__ . '/FileInfo.latte');
                $template->fileinfo = $this->context->files->fileDetails($actualdir, $this->filename);
                $template->render();
        }
}