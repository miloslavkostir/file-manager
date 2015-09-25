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

use Ixtrum\FileManager\Application\FileSystem\Finder;

/**
 * File info control.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class FileInfo extends \Ixtrum\FileManager\Application\Controls
{

    /**
     * Render control
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . "/FileInfo.latte");
        $this->template->setTranslator($this->system->getService("translator"));
        $this->template->resUrl = $this->system->parameters["resUrl"];
        $this->template->resDir = $this->system->parameters["resDir"];
        $this->template->timeFormat = $this->system->getService("translator")->getTimeFormat();

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
                $this->parent->parent->flashMessage($this->system->getService("translator")->translate("'%s' already does not exist!", $filename), "warning");
            }
        }

        $this->template->render();
    }

    /**
     * Get file details
     *
     * @param string $dir      Dir
     * @param string $filename File name
     *
     * @return array
     */
    private function getFileInfo($dir, $filename)
    {
        $path = $this->getAbsolutePath($dir) . DIRECTORY_SEPARATOR . $filename;

        $info = array();
        $info["name"] = $filename;
        $info["modificated"] = date("F d Y H:i:s", filemtime($path));
        $info["permissions"] = $this->system->getService("filesystem")->getFileMod($path);
        $info["dir"] = false;

        if (is_file($path)) {

            $info["extension"] = pathinfo($path, PATHINFO_EXTENSION);
            $info["size"] = $this->system->getService("filesystem")->getSize($path);
        } else {

            $info["dir"] = true;
            $info["size"] = $this->system->getService("filesystem")->getSize($path);
            $info["filesCount"] = $this->getDirFilesCount($path);
        }
        return $info;
    }

    /**
     * Get files count in directory
     *
     * @param string $path Dir path
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
     * @param string  $dir     Dir
     * @param array   $files   Files
     * @param boolean $iterate Iterate? (optional)
     *
     * @return array
     */
    private function getFilesInfo($dir, $files, $iterate = true)
    {
        $path = $this->getAbsolutePath($dir);
        $info = array(
            "size" => 0,
            "dirCount" => 0,
            "filesCount" => 0
        );

        foreach ($files as $file) {

            $filePath = $path . DIRECTORY_SEPARATOR . $file;
            if (!is_dir($filePath)) {

                $info['size'] += $this->system->getService("filesystem")->getSize($filePath);
                $info['filesCount']++;
            } elseif ($iterate) {

                $info['dirCount']++;
                $items = Finder::find('*')->from($filePath);

                foreach ($items as $item) {

                    if ($item->isDir()) {
                        $info['dirCount']++;
                    } else {
                        $info['size'] += $this->system->getService("filesystem")->getSize($item->getPathName());
                        $info['filesCount']++;
                    }
                }
            }
        }
        return $info;
    }

}