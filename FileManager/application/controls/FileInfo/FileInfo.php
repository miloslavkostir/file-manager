<?php

namespace Ixtrum\FileManager\Application\Controls;

use Ixtrum\FileManager\Application\FileSystem\Finder;

class FileInfo extends \Ixtrum\FileManager\Application\Controls
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/FileInfo.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->thumbDir = $this->system->parameters["resDir"] . "/img/icons/large";

        if (count($this->selectedFiles) > 1) {

            $this->template->files = $this->getFilesInfo(
                    $this->getActualDir(), $this->selectedFiles
            );
        } elseif (isset($this->selectedFiles[0])) {

            $actualDir = $this->getActualDir();
            $filename = $this->selectedFiles[0];
            if ($this->isPathValid($actualDir, $filename)) {
                $this->template->file = $this->getFileInfo($actualDir, $filename);
            } else {
                // @todo messages in this phase can not be rendered?
                $this->parent->parent->flashMessage($this->system->translator->translate("'%s' already does not exist!", $filename), "warning");
            }
        }

        $this->template->render();
    }

    /**
     * Get file details
     *
     * @param string $dir
     * @param string $filename
     *
     * @return array
     */
    private function getFileInfo($dir, $filename)
    {
        $thumbDir = $this->system->parameters["resDir"] . "/img/icons/large";
        $path = $this->getAbsolutePath($dir) . DIRECTORY_SEPARATOR . $filename;

        $info = array();
        if (!is_dir($path)) {

            $info["path"] = $path;
            $info["actualdir"] = $dir;
            $info["filename"] = $filename;
            $info["type"] = pathinfo($path, PATHINFO_EXTENSION);
            $info["size"] = $this->system->filesystem->getSize($path);
            $info["modificated"] = date("F d Y H:i:s", filemtime($path));
            $info["permissions"] = $this->system->filesystem->getFileMod($path);

            if (file_exists($this->system->parameters["wwwDir"] . "/$thumbDir/" . strtolower($info["type"]) . ".png" && strtolower($info["type"]) <> "folder")) {
                $info["icon"] = "/$thumbDir/" . $info["type"] . ".png";
            } else {
                $info["icon"] = "/$thumbDir/" . "icon.png";
            }
        } else {

            $info["path"] = $path;
            $info["actualdir"] = $dir;
            $info["filename"] = $filename;
            $info["type"] = "folder";
            $info["size"] = $this->system->filesystem->getSize($path);
            $info["files_count"] = $this->getDirFilesCount($path);
            $info["modificated"] = date("F d Y H:i:s", filemtime($path));
            $info["permissions"] = $this->system->filesystem->getFileMod($path);
            $info["icon"] = $thumbDir . "folder.png";
        }

        return $info;
    }

    /**
     * Get files count in directory
     *
     * @param string $path Path to folder
     *
     * @return integer
     */
    private function getDirFilesCount($path)
    {
        $count = 0;
        $files = Finder::findFiles("*")->from($path);
        foreach ($files as $file) {

            if ($file->isFile()) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get info about files
     *
     * @param string  $dir
     * @param array   $files
     * @param boolean $iterate
     *
     * @return array
     */
    private function getFilesInfo($dir, $files, $iterate = true)
    {
        $path = $this->getAbsolutePath($dir);
        $info = array(
            "size" => 0,
            "dirCount" => 0,
            "fileCount" => 0
        );

        foreach ($files as $file) {

            $filePath = $path . DIRECTORY_SEPARATOR . $file;
            if (!is_dir($filePath)) {

                $info['size'] += $this->system->filesystem->getSize($filePath);
                $info['fileCount']++;
            } elseif ($iterate) {

                $info['dirCount']++;
                $items = Finder::find('*')->from($filePath);

                foreach ($items as $item) {

                    if ($item->isDir()) {
                        $info['dirCount']++;
                    } else {
                        $info['size'] += $this->system->filesystem->getSize($item->getPathName());
                        $info['fileCount']++;
                    }
                }
            }
        }

        return $info;
    }

}