<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Application\FileSystem;

/**
 * Get file size, even for big files over 2 GB
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class FileSize
{

    /** @var string $path File path */
    protected $path;

    /**
     * Constructor
     *
     * @param string $path File path
     *
     * @throws \Exception
     */
    public function __construct($path)
    {
        if (!function_exists("bcadd")) {
            throw new \Exception("You must have installed BC Math extension!");
        }
        if (!is_file($path)) { // @todo problem on 32-bit systems with files > 2GB
            throw new \Exception("Can not find file '$path'!");
        }
        $this->path = $path;
    }

    /**
     * Get file size in Bytes
     *
     * @return string Bytes
     *
     * @throws \Exception
     */
    public function getSize()
    {
        if ($this->sizeCurl() !== false) {
            return $this->sizeCurl();
        }
        if ($this->sizeNativeSeek() !== false) {
            return $this->sizeNativeSeek();
        }
        if ($this->sizeCom() !== false) {
            return $this->sizeCom();
        }
        if ($this->sizeExec() !== false) {
            return $this->sizeExec();
        }
        if ($this->sizeNativeRead() !== false) {
            return $this->sizeNativeRead();
        }
        throw new \Exception("File size error at file '$this->path'");
    }

    /**
     * Returns file size by using native fseek function
     *
     * @see http://www.php.net/manual/en/function.filesize.php#79023
     * @see http://www.php.net/manual/en/function.filesize.php#102135
     *
     * @return string | bool (false when fail)
     */
    private function sizeNativeSeek()
    {
        // This should work for large files on 64bit platforms and for small files every where
        $fp = fopen($this->path, "rb");
        flock($fp, LOCK_SH);

        if (!$fp) {
            return false;
        }

        $res = fseek($fp, 0, SEEK_END);
        if ($res === 0) {

            $pos = ftell($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            // $pos will be positive int if file is <2GB
            // if is >2GB <4GB it will be negative number
            if ($pos >= 0) {
                return (string) $pos;
            } else {
                return sprintf("%u", $pos);
            }
        } else {
            flock($fp, LOCK_UN);
            fclose($fp);
            return false;
        }
    }

    /**
     * Returns file size by using native fread function
     *
     * @see http://stackoverflow.com/questions/5501451/php-x86-how-to-get-filesize-of-2gb-file-without-external-program/5504829#5504829
     *
     * @return string | false
     */
    private function sizeNativeRead()
    {
        $fp = fopen($this->path, "rb");
        flock($fp, LOCK_SH);

        if (!$fp) {
            return false;
        }

        rewind($fp);
        $offset = PHP_INT_MAX - 1;

        $size = (string) $offset;
        if (fseek($fp, $offset) !== 0) {

            flock($fp, LOCK_UN);
            fclose($fp);
            return false;
        }

        $chunksize = 1024 * 1024;
        while (!feof($fp)) {

            $readed = strlen(fread($fp, $chunksize));
            $size = bcadd($size, $readed);
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return $size;
    }

    /**
     * Returns file size using curl module
     *
     * @see http://www.php.net/manual/en/function.filesize.php#100434
     *
     * @return string | false
     */
    private function sizeCurl()
    {
        // If program goes here, file must be larger than 2GB
        // curl solution - cross platform and really cool :)
        if (function_exists("curl_init")) {

            $ch = @curl_init("file://$this->path");
            if (!$ch) {
                return false;
            }

            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $data = curl_exec($ch);
            curl_close($ch);

            if ($data !== false && preg_match("/Content-Length: (\d+)/", $data, $matches)) {
                return (string) $matches[1];
            }
        }

        return false;
    }

    /**
     * Returns file size by using external program (exec needed)
     *
     * @see http://stackoverflow.com/questions/5501451/php-x86-how-to-get-filesize-of-2gb-file-without-external-program/5502328#5502328
     *
     * @return string | false
     */
    private function sizeExec()
    {
        // filesize using exec
        if (function_exists("exec")) {

            $escapedPath = escapeshellarg($this->path);
            if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') { // Windows
                // Try using the NT substition modifier %~z
                $size = trim(exec("for %F in ($escapedPath) do @echo %~zF"));
            } else { // other OS
                // If the platform is not Windows, use the stat command (should work for *nix and MacOS)
                $size = trim(exec("stat -c%s $escapedPath"));
            }

            // If the return is not blank, not zero, and is number
            if ($size && ctype_digit($size)) {
                return (string) $size;
            }
        }
        return false;
    }

    /**
     * Returns file size by using Windows COM interface
     *
     * @see http://stackoverflow.com/questions/5501451/php-x86-how-to-get-filesize-of-2gb-file-without-external-program/5502328#5502328
     *
     * @return string | boolean
     */
    private function sizeCom()
    {
        if (class_exists("COM")) {

            // Use the Windows COM interface
            $fsobj = new \COM("Scripting.FileSystemObject");
            $path = $this->path;
            if (dirname($path) == ".") {
                $path = ((substr(getcwd(), -1) == DIRECTORY_SEPARATOR) ? getcwd() . basename($this->path) : getcwd() . DIRECTORY_SEPARATOR . basename($this->path));
            }
            $f = $fsobj->GetFile($path);

            return (string) $f->Size;
        }
    }

}