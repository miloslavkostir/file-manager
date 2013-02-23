<?php

namespace Ixtrum\FileManager\Application;

use Ixtrum\FileManager\Application\FileSystem\Finder;

class FileSystem
{

    /**
     * Get unique file/folder path
     *
     * @param string $path Path to file/folder
     *
     * @return string
     */
    public static function getUniquePath($path)
    {
        if (file_exists($path)) {

            $filename = basename($path);
            $dirname = dirname($path);
            $i = 1;
            while (file_exists($path = "$dirname/$i" . "_$filename")) {
                $i++;
            }
        }
        return $path;
    }

    /**
     * Copy file/folder to destination folder
     *
     * @param string  $source      Source file/folder path
     * @param string  $destination Destination folder path
     * @param boolean $overwrite   Overwrite file/folder if exist
     *
     * @throws \Exception
     */
    public function copy($source, $destination, $overwrite = false)
    {
        if (!is_dir($destination)) {
            throw new \Exception("Destination must be existing folder, but '$destination' given!");
        }

        // Get destination file/folder path
        $fileName = basename($source);
        if ($overwrite) {
            $destination .= DIRECTORY_SEPARATOR . $fileName;
        } else {
            $destination = self::getUniquePath($destination . DIRECTORY_SEPARATOR . $fileName);
        }

        if (is_dir($source)) {
            $this->copyDir($source, $destination);
        } else {
            $this->copyFile($source, $destination);
        }
    }

    /**
     * Copy directory
     *
     * @param string $source      Source folder
     * @param string $destination Destination folder
     *
     * @throws \Exception
     */
    public function copyDir($source, $destination)
    {
        if (!is_dir($source)) {
            throw new \Exception("Source must be existing folder, but '$source' given!");
        }

        // Create destination folder if not exists
        if (!is_dir($destination)) {
            $this->mkdir($destination);
        }

        foreach (Finder::find("*")->in($source) as $file) {

            $destPath = $destination . DIRECTORY_SEPARATOR . $file->getFilename();
            if ($file->isDir()) {
                $this->copyDir($file->getRealPath(), $destPath);
            } else {
                $this->copyFile($file->getRealPath(), $destPath);
            }
        }
    }

    /**
     * Copy file (chunked)
     *
     * @param string $src  Source file
     * @param string $dest Destination file
     *
     * @throws \Exception
     */
    public function copyFile($src, $dest)
    {
        if (!is_file($src)) {
            throw new \Exception("Source must be existing file, but '$src' given!");
        }

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
    public static function getRootname()
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