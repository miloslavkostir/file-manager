<?php

namespace Ixtrum\FileManager\Application;

use Nette\Utils\Finder,
    Nette\Diagnostics\Debugger,
    Nette\DI\Container;

class FileSystem
{

    /** @var Container */
    private $context;

    /** @var array */
    private $config;

    public function __construct(Container $container = NULL, $config = array())
    {
        $this->config = $config;
        $this->context = $container;
    }

    /**
     * Check if file exists and give alternative name
     *
     * @param  string  actual dir (absolute path)
     * @param  string  filename
     * @return string
     */
    public function checkDuplName($targetpath, $filename)
    {
        if (file_exists($targetpath . $filename)) {

            $i = 1;
            while (file_exists($targetpath . $i . "_$filename")) {
                $i++;
            }

            return $i . "_$filename";
        } else {
            return $filename;
        }
    }

    /**
     * Copy file or folder from disk
     *
     * @param  string  actual dir (relative path)
     * @param  string  target dir (relative path)
     * @param  string  filename
     * @return bool
     */
    public function copy($actualdir, $targetdir, $filename)
    {
        $actualpath = $this->getAbsolutePath($actualdir);
        $targetpath = $this->getAbsolutePath($targetdir);

        if (is_writable($targetpath)) {

            $disksize = $this->diskSizeInfo();

            if ($this->config["cache"]) {

                $caching = new Caching($this->context, $this->config);
                $caching->deleteItem(array("content", $this->getRealPath($targetpath)));
            }

            if (is_dir($actualpath . $filename)) {

                if ($this->config["cache"]) {

                    $caching->deleteItem(NULL, array("tags" => "treeview"));
                    $caching->deleteItem(array('content', $this->getRealPath($targetpath)));
                }

                $dirinfo = $this->getFolderInfo(realpath($actualpath . $filename));
                if ($disksize["spaceleft"] < $dirinfo["size"]) {
                    return false;
                } else {

                    if (!$this->isSubFolder($actualpath, $targetpath, $filename)) {

                        if (!$this->copyFolder($actualpath . $filename, $targetpath . $this->checkDuplName($targetpath, $filename))) {
                            return false;
                        };
                    }
                    return false;
                }
            } else {

                if ($this->copyFile($actualpath . $filename, $targetpath . $this->checkDuplName($targetpath, $filename))) {
                    return true;
                }
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Copy file (chunked)
     *
     * @param string $src (absolute path)
     * @param string $dest (absolute path)
     * @return integer bytes written
     * @return bool
     */
    public function copyFile($src, $dest)
    {
        if (!$this->validateFileSize($src)) {
            return false;
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

        return true;
    }

    /**
     * Copy folder recursively
     *
     * @param  string  actual dir (absolute path)
     * @param  string  target dir (absolute path)
     * @return bool
     */
    public function copyFolder($src, $dst)
    {
        $dir = opendir($src);
        $this->mkdir($dst);

        while (false !== ($file = readdir($dir))) {

            if (( $file != "." ) && ( $file != '..' )) {

                if (is_dir("$dst/$file")) {
                    $this->copyFolder("$src/$file", "$dst/$file");
                } else {
                    if (!$this->copyFile("$src/$file", "$dst/$file")) {
                        return false;
                    };
                }
            }
        }

        closedir($dir);
        return true;
    }

    /**
     * Check if target folder is it's sub-folder
     *
     * @param  string  actual dir (absolute path)
     * @param  string  target dir (absolute path)
     * @param  string  filename
     * @return bool
     */
    public function isSubFolder($actualpath, $targetpath, $filename)
    {
        $state = false;

        $folders = Finder::findDirectories('*')->from(realpath($actualpath . $filename));
        foreach ($folders as $folder) {

            if ($folder->getRealPath() == $this->getRealPath($targetpath)) {
                $state = true;
            }
        }

        return $state;
    }

    /**
     * Move file or folder
     *
     * @param  string  actual folder (relative path)
     * @param  string  target folder (relative path)
     * @param  string  filename
     * @return bool
     */
    public function move($actualdir, $targetdir, $filename)
    {
        $actualpath = $this->getAbsolutePath($actualdir);
        $targetpath = $this->getAbsolutePath($targetdir);

        if ($actualdir == $targetdir) {
            return false;
        } else {
            if (is_dir($actualpath . $filename)) {

                $thumbs = new Thumbs($this->context, $this->config);
                $thumbs->deleteDirThumbs($actualpath . $filename);

                if ($this->isSubFolder($actualpath, $targetpath, $filename)) {
                    return false;
                } elseif ($this->moveFolder($actualpath, $targetpath, $filename)) {

                    if ($this->config["cache"]) {

                        $caching = new Caching($this->context, $this->config);
                        $caching->deleteItemsRecursive($actualpath . $filename);
                        $caching->deleteItem(array("content", $this->getRealPath($actualpath)));
                        $caching->deleteItem(array("content", $this->getRealPath($targetpath)));
                    }

                    if ($this->deleteFolder($actualpath . $filename)) {
                        return true;
                    }

                    return false;
                } else {
                    return false;
                }
            } else {

                $thumbs = new Thumbs($this->context, $this->config);
                $thumbs->deleteThumb($actualpath . $filename);

                if ($this->moveFile($actualpath, $targetpath, $filename)) {

                    if ($this->config["cache"]) {

                        $caching = new Caching($this->context, $this->config);
                        $caching->deleteItem(array("content", $this->getRealPath($actualpath)));
                        $caching->deleteItem(array("content", $this->getRealPath($targetpath)));
                    }

                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * Move file
     *
     * @param  string  actual folder (absolute path)
     * @param  string  target folder (absolute path)
     * @param  string  filename
     * @return bool
     */
    public function moveFile($actualpath, $targetpath, $filename)
    {
        if (rename($actualpath . $filename, $targetpath . $this->checkDuplName($targetpath, $filename))) {

            $application = new \Ixtrum\FileManager\Application($this->context->session);
            $application->clearClipboard();
            return true;
        }

        return false;
    }

    /**
     * Move folder
     * @param  string  from (absolute path)
     * @param  string  to (absolute path)
     * @param  string  what
     * @return bool
     */
    public function moveFolder($actualPath, $targetPath, $filename)
    {
        if (!is_dir($targetPath . $filename)) {
            $this->mkdir($targetPath . $filename);
        }

        $files = Finder::find("*")->in($actualPath . $filename);
        foreach ($files as $file) {

            if ($file->isDir()) {
                $this->moveFolder($file->getPath() . "/", $targetPath . "$filename/", $file->getFilename());
            } elseif (!rename($file->getPathName(), $targetPath . "$filename/" . $file->getFileName())) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get permissions
     *
     * @see http://php.net/manual/en/function.fileperms.php
     * @param string $path
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
     * Get file details
     *
     * @param string $actualdir
     * @param string $filename
     *
     * @return array
     */
    public function fileDetails($actualdir, $filename)
    {
        $thumbDir = $this->config["resDir"] . "img/icons/large/";
        $path = $this->getAbsolutePath($actualdir) . $filename;

        $info = array();
        if (!is_dir($path)) {

            $info["path"] = $path;
            $info["actualdir"] = $actualdir;
            $info["filename"] = $filename;
            $info["type"] = pathinfo($path, PATHINFO_EXTENSION);
            $info["size"] = $this->filesize($path);
            $info["modificated"] = date("F d Y H:i:s", filemtime($path));
            $info["permissions"] = $this->getFileMod($path);

            if (file_exists($this->config["wwwDir"] . $thumbDir . strtolower($info["type"]) . ".png" && strtolower($info["type"]) <> "folder")) {
                $info["icon"] = $thumbDir . $info["type"] . ".png";
            } else {
                $info["icon"] = $thumbDir . "icon.png";
            }
        } else {

            $folder_info = $this->getFolderInfo($path);
            $info["path"] = $path;
            $info["actualdir"] = $actualdir;
            $info["filename"] = $filename;
            $info["type"] = "folder";
            $info["size"] = $folder_info["size"];
            $info["files_count"] = $folder_info["count"];
            $info["modificated"] = date("F d Y H:i:s", filemtime($path));
            $info["permissions"] = $this->getFileMod($path);
            $info["icon"] = $thumbDir . "folder.png";
        }

        return $info;
    }

    /**
     * Get info about files
     *
     * @param string $dir
     * @param array $files
     * @param bool $iterate
     * @return array
     */
    public function getFilesInfo($dir, $files, $iterate = false)
    {
        $path = $this->getAbsolutePath($dir);
        $info = array(
            "size" => 0,
            "dirCount" => 0,
            "fileCount" => 0
        );

        foreach ($files as $file) {

            $filepath = $path . $file;
            if (!is_dir($filepath)) {

                $info['size'] += $this->filesize($filepath);
                $info['fileCount']++;
            } elseif ($iterate) {

                $info['dirCount']++;
                $items = Finder::find('*')->from($filepath);

                foreach ($items as $item) {

                    if ($item->isDir()) {
                        $info['dirCount']++;
                    } else {
                        $info['size'] += $this->filesize($item->getPathName());
                        $info['fileCount']++;
                    }
                }
            }
        }

        return $info;
    }

    /**
     * Get file size for files > 2 GB
     *
     * @param string
     * @return integer | false
     */
    public function filesize($path)
    {
        if (!file_exists($path)) {
            Debugger::log("File does not exist.", Debugger::WARNING);
        }

        if (!is_file($path)) {
            Debugger::log("File is not file.", Debugger::WARNING);
        }

        if (filesize($path) === 0) {
            return null;
        }

        $filesize = new FileSystem\Filesize;

        $return = $filesize->sizeCurl($path);
        if ($return) {
            return $return;
        }

        $return = $filesize->sizeNativeSeek($path);
        if ($return) {
            return $return;
        }

        $return = $filesize->sizeCom($path);
        if ($return) {
            return $return;
        }

        $return = $filesize->sizeExec($path);
        if ($return) {
            return $return;
        }

        $return = $filesize->sizeNativeRead($path);
        if ($return) {
            return $return;
        }

        Debugger::log("File size error at file $path.", Debugger::WARNING);

        return false;
    }

    /**
     * Get full dir info about size and subfolders count
     *
     * @param string $path
     * @return array
     */
    public function getFolderInfo($path)
    {
        $info = array();
        $info["size"] = 0;
        $info["count"] = 0;
        $files = Finder::findFiles("*")->from($path);

        foreach ($files as $file) {

            $info["size"] += $this->filesize($file->getPathName());
            $info["count"]++;
        }

        return $info;
    }

    /**
     * Get safe folder name
     *
     * @param string $name
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
     * @param string $name
     * @return string
     */
    public function safeFilename($name)
    {
        $except = array("\\", "/", ":", "*", "?", '"', "<", ">", "|");
        $name = str_replace($except, "", $name);

        return \Nette\Utils\Strings::toAscii($name);
    }

    /**
     * Delete file or folder from disk
     *
     * @param  string  folder (relative path)
     * @param  string  filename - optional
     * @return bool
     */
    public function delete($dir, $file = "")
    {
        $absDir = $this->getAbsolutePath($dir);

        if (is_dir($absDir . $file)) {

            if ($this->config["cache"]) {

                $caching = new Caching($this->context, $this->config);
                $caching->deleteItemsRecursive($absDir);
            }

            $thumbs = new Thumbs($this->context, $this->config);
            $thumbs->deleteDirThumbs($absDir . $file);

            if ($this->deleteFolder($absDir . $file)) {
                return true;
            }

            return false;
        } else {

            if ($this->deleteFile($dir, $file)) {
                return true;
            }

            return false;
        }
    }

    /**
     * Delete file
     *
     * @param  string  relative folder path
     * @param  string  filename
     * @return bool
     */
    public function deleteFile($actualdir, $filename)
    {
        $path = $this->getAbsolutePath($actualdir);

        if (is_writable($path)) {

            // delete thumb
            $thumbs = new Thumbs($this->context, $this->config);
            $thumbs->deleteThumb($path . $filename);

            // delete source file
            if (@unlink($path . $filename)) {

                if ($this->config["cache"]) {

                    $caching = new Caching($this->context, $this->config);
                    $caching->deleteItem(array("content", $this->getRealPath($path)));
                }

                return true;
            }

            return false;
        } else {
            return false;
        }
    }

    /**
     * Delete folder recursively
     *
     * @param string absolute path
     * @return bool
     */
    public function deleteFolder($directory)
    {
        if (!is_dir($directory) || !is_readable($directory)) {
            return false;
        }

        $contents = Finder::find("*")->in($directory);
        foreach ($contents as $item) {

            if ($item->isDir()) {
                $this->deleteFolder($item->getPathName());
            } else {

                if ($item->isWritable()) {
                    unlink($item->getPathName());
                } else {
                    return false;
                }
            }
        }

        if (!@rmdir($directory)) {
            return false;
        }

        return true;
    }

    /**
     * Create dir
     *
     * @param string $targetPath (absolute)
     * @return boolean
     */
    public function mkdir($targetPath)
    {
        $oldumask = umask(0);
        if (mkdir($targetPath, 0777)) {
            return true;
        }

        umask($oldumask);

        return false;
    }

    /**
     * Get absolute path from relative path
     *
     * @param string $actualdir
     * @return string
     */
    public function getAbsolutePath($actualdir)
    {
        if ($actualdir == $this->getRootname()) {
            return $this->config["uploadroot"] . $this->config["uploadpath"];
        }

        return $this->config["uploadroot"] . substr($this->config["uploadpath"], 0, -1) . $actualdir;
    }

    /**
     * Repair (back)slashes according to OS
     *
     * @param string $path
     * @return string
     */
    public function getRealPath($path)
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));
        if ($os === "WIN") {
            $path = str_replace("/", "\\", $path);
        } else {
            $path = str_replace("\\", "/", $path);
        }

        if (realpath($path)) {

            $path = realpath($path);
            if (is_dir($path)) {

                if ($os === "WIN" && substr($path, -1) <> "\\") {
                    $path .= "\\";
                }

                if ($os <> "WIN" && substr($path, -1) <> "/") {
                    $path .= "/";
                }
            }

            return $path;
        } else {
            throw new InvalidArgumentException("Invalid path '$path' given!");
        }
    }

    /**
     * Get root folder name
     *
     * @return string
     */
    public function getRootname()
    {
        $path = $this->config["uploadpath"];
        $first = substr($path, 0, 1);
        $last = substr($path, -1, 1);

        if (($first === "/" || $first === "\\") && ($last === "/" || $last === "\\")) {
            $path = substr($path, 1, strlen($path) - 2);
        } else {
            throw new \Nette\InvalidArgumentException("Invalid upload path '$path' given! Correct path starts & ends with \ (Windows) or / (Unix).");
        }

        return $path;
    }

    /**
     * Get used disk space
     *
     * @return integer bytes
     */
    public function getUsedSize()
    {
        $size = 0;
        $files = Finder::findFiles("*")->from($this->config["uploadroot"] . $this->config["uploadpath"]);

        foreach ($files as $file) {
            $size += $this->filesize($file->getPathName());
        }

        return $size;
    }

    /**
     * Get details about used disk size
     *
     * @return array
     */
    public function diskSizeInfo()
    {
        $info = array();
        if ($this->config["quota"]) {

            $size = $this->getUsedSize();
            $info["usedsize"] = $size;
            $info["spaceleft"] = ($this->config["quota_limit"] * 1048576) - $size;
            $info["percentused"] = round(($size / ($this->config["quota_limit"] * 1048576)) * 100);
        } else {

            $path = $this->config["uploadroot"] . $this->config["uploadpath"];
            $freesize = disk_free_space($path);
            $totalsize = disk_total_space($path);
            $info["usedsize"] = $totalsize - $freesize;
            $info["spaceleft"] = $freesize;
            $info["percentused"] = round(($info["usedsize"] / $totalsize ) * 100);
        }

        return $info;
    }

    /**
     * Check if is enough space on disk for file
     *
     * @param string $path
     * @return bool
     */
    public function validateFileSize($path)
    {
        $diskInfo = $this->diskSizeInfo();
        $needSpace = $diskInfo["spaceleft"] - $this->filesize($path);

        if ($needSpace > 0) {
            return true;
        }

        return false;
    }

    /**
     * Check if realtive path is valid
     *
     * @param string $dir
     * @param string $file (optional)
     * @return bool
     */
    public function validPath($dir, $file = NULL)
    {
        $path = $this->getAbsolutePath($dir);
        if ($file) {
            $path .= $file;
        }

        if (file_exists($path)) {
            return true;
        }

        return false;
    }

}