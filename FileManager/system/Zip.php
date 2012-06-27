<?php

namespace Ixtrum\FileManager\Application;

use Nette\Utils\Finder,
        Nette\Application\ApplicationException,
        Nette\Utils\Strings;


class Zip
{
        /** @var string */
        private $targetDir;


        public function __construct($targetDir)
        {
                if (!extension_loaded("zip"))
                        throw new ApplicationException("PHP ZIP not loaded.");


                if (!is_dir($targetDir)) {

                        $oldumask = umask(0);
                        mkdir($targetDir, 0777);
                        umask($oldumask);
                }

                $this->targetDir = $targetDir;
        }


        /**
         * Zip files from list
         * 
         * @param array $files
         */
        public function addFiles($files)
        {
                $zip = new \ZipArchive;
                $filesClass = new FileSystem;

                $name = $filesClass->checkDuplName($this->targetDir, Date("Ymd_H-m-s") . ".zip");
                $zipPath = "$this->targetDir/$name";

                if ($zip->open($zipPath, \ZipArchive::CREATE)) {

                        $path = $this->targetDir;
                        foreach ($files as $file) {

                                $name = $file;
                                $file = $path . $file;

                                if (is_dir($file)) {

                                        $iterator = Finder::find("*")->from($file);
                                        foreach ($iterator as $item) {

                                                $name = substr_replace($item->getPathname(), "", 0, strlen($path));
                                                if ($item->isFile())
                                                        $zip->addFile($item->getRealPath(), $name);

                                                if ($item->isDir())
                                                        $zip->addEmptyDir($name);
                                        }
                                } else
                                        $zip->addFile($file, $name);
                        }

                        $zip->close();
                 } else
                        throw new ApplicationException("Can not create ZIP archive '$zipPath' from '$this->targetDir'.");
        }
}