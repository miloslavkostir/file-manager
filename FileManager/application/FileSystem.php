<?php

namespace Ixtrum\FileManager\Application;

use Ixtrum\FileManager\Application\FileSystem\Finder;

class FileSystem
{

    /**
     * Check if file/folder exists and give alternative path name
     *
     * @param string $path Path to file/folder
     *
     * @return string
     */
    public function checkDuplName($path)
    {
        if (file_exists($path)) {

            $filename = pathinfo($path, PATHINFO_BASENAME);
            $dirname = pathinfo($path, PATHINFO_DIRNAME);
            $i = 1;
            while (file_exists("$dirname/$i" . "_$filename")) {
                $i++;
            }
            return "$dirname/$i" . "_$filename";
        } else {
            return $path;
        }
    }

    /**
     * Copy file/folder from one location to another location
     *
     * @param string  $source      Source
     * @param string  $destination Destination
     * @param boolean $overwrite   Overwrite file/folder if exist
     */
    public function copy($source, $destination, $overwrite = false)
    {
        if (!$overwrite) {
            $destination = $this->checkDuplName($destination);
        }

        if (is_dir($source)) {

            // Create destination folder if not exists
            if (!is_dir($destination)) {
                $this->mkdir($destination);
            }

            $files = Finder::find("*")->in($source);
            foreach ($files as $file) {

                $filename = $file->getFilename();
                $this->copy($file->getRealPath(), "$destination/$filename", $overwrite);
            }
        } else {
            $this->copyFile($source, $destination);
        }
    }

    /**
     * Copy file (chunked)
     *
     * @param string $src  Source file
     * @param string $dest Destination file
     */
    private function copyFile($src, $dest)
    {
        $buffer_size = 1048576;
        $ret = 0;
        $fin = fopen($src, "rb");
        $fout = fopen($dest, "w");

        while (!feof($fin)) {
            $ret += fwrite($fout, fread($fin, $buffer_size));
        }

        fclose($fin);
        fclose($fout);
    }

    /**
     * Check if destination folder is located in it's sub-folder
     *
     * @param string $root Original folder path
     * @param string $dir  Tested folder path
     *
     * @return boolean
     */
    public function isSubFolder($root, $dir)
    {
        if (!is_dir($root)) {
            throw new \Exception("'$dir' is not directory!");
        }

        if ($root === $dir) {
            return false;
        }
        return strpos($dir, $root) === 0;
    }

    /**
     * Get permissions
     *
     * @link http://php.net/manual/en/function.fileperms.php
     *
     * @param string $path Path to file
     *
     * @return string
     */
    public function getFileMod($path)
    {
        $perms = fileperms($path);

        if (($perms & 0xC000) == 0xC000) {
            // Socket
            $info = 's';
        } elseif (($perms & 0xA000) == 0xA000) {
            // Symbolic Link
            $info = 'l';
        } elseif (($perms & 0x8000) == 0x8000) {
            // Regular
            $info = '-';
        } elseif (($perms & 0x6000) == 0x6000) {
            // Block special
            $info = 'b';
        } elseif (($perms & 0x4000) == 0x4000) {
            // Directory
            $info = 'd';
        } elseif (($perms & 0x2000) == 0x2000) {
            // Character special
            $info = 'c';
        } elseif (($perms & 0x1000) == 0x1000) {
            // FIFO pipe
            $info = 'p';
        } else {
            // Unknown
            $info = 'u';
        }

        // Owner
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ?
                        (($perms & 0x0800) ? 's' : 'x' ) :
                        (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ?
                        (($perms & 0x0400) ? 's' : 'x' ) :
                        (($perms & 0x0400) ? 'S' : '-'));

        // World
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ?
                        (($perms & 0x0200) ? 't' : 'x' ) :
                        (($perms & 0x0200) ? 'T' : '-'));

        return $info;
    }

    /**
     * Get safe folder name
     *
     * @param string $name Folder name
     *
     * @return string
     */
    public function safeFoldername($name)
    {
        $except = array('\\', '/', ':', '*', '?', '"', '<', '>', '|');
        $name = str_replace($except, '', $name);

        if (substr($name, 0) == ".") {
            $name = "";
        } elseif (str_replace(array('.', ' '), '', $name) == "") {  # because of this: .. .
            $name = "";
        }

        return \Nette\Utils\Strings::toAscii($name);
    }

    /**
     * Get safe file name
     *
     * @param string $name File name
     *
     * @return string
     */
    public function safeFilename($name)
    {
        $except = array("\\", "/", ":", "*", "?", '"', "<", ">", "|");
        $name = str_replace($except, "", $name);

        return \Nette\Utils\Strings::toAscii($name);
    }

    /**
     * Delete file or folder
     *
     * @param string $path Path to file/folder
     *
     * @return boolean
     */
    public function delete($path)
    {
        if (!is_writable($path) || !is_readable($path)) {
            return false;
        }

        if (is_dir($path)) {

            foreach (Finder::find("*")->in($path) as $item) {

                if ($item->isDir()) {
                    $this->delete($item->getRealPath());
                } else {
                    unlink($item->getPathName());
                }
            }

            if (!@rmdir($path)) {
                return false;
            }
        } else {
            unlink($path);
            return true;
        }

        return true;
    }

    /**
     * Create folder
     *
     * @param string  $path Path to folder
     * @param integer $mask Folder mask
     *
     * @return boolean
     */
    public function mkdir($path, $mask = 0777)
    {
        $oldumask = umask(0);
        if (mkdir($path, $mask)) {
            umask($oldumask);
            return true;
        }
        umask($oldumask);
        return false;
    }

    /**
     * Get root folder name
     *
     * @return string
     */
    public function getRootname()
    {
        return "/";
    }

    /**
     * Get file/folder size
     *
     * @param string $path Path to file/folder
     *
     * @return integer
     *
     * @throws \Exception
     */
    public function getSize($path)
    {
        if (is_dir($path)) {

            $size = 0;
            $files = Finder::findFiles("*")->from($path);
            foreach ($files as $file) {
                $size += $this->getSize($file->getPathName());
            }
            return $size;
        } else {

            $fileSize = new FileSystem\FileSize($path);

            $size = $fileSize->sizeCurl();
            if ($size)
                return $size;

            $size = $fileSize->sizeNativeSeek();
            if ($size)
                return $size;

            $size = $fileSize->sizeCom();
            if ($size)
                return $size;

            $size = $fileSize->sizeExec();
            if ($size)
                return $size;

            $size = $fileSize->sizeNativeRead();
            if ($size)
                return $size;

            throw new \Exception("File size error at file '$path'");
        }
    }

}