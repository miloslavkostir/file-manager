<?php

namespace Ixtrum\FileManager\Application;

class Resources
{

    /** @var string */
    private $resPath;

    /** @var string */
    private $rootPath;

    /**
     * Constructor
     *
     * @param string $resPath  path to target res dir
     * @param string $rootPath path to filemanager root
     */
    public function __construct($resPath, $rootPath)
    {
        if (!is_dir($rootPath)) {
            throw new \Nette\DirectoryNotFoundException("Directory '$rootPath' does not exist.");
        }
        $this->resPath = $this->normalizeDirPath($resPath);
        $this->rootPath = $this->normalizeDirPath($rootPath);
    }

    /**
     * Synchronize new and modified files
     */
    public function synchronize()
    {
        $this->copyFolder("$this->rootPath/resources", $this->resPath);
    }

    /**
     * Copy resources recursively
     *
     * @todo support for big files over 2 GB
     *
     * @param string $src source
     * @param string $dst destination
     *
     * @return boolean
     */
    public function copyFolder($src, $dst)
    {
        if (!is_dir($dst)) {
            $oldumask = umask(0);
            mkdir($dst, 0777);
            umask($oldumask);
        }

        $files = \Nette\Utils\Finder::find("*")->in($src);
        foreach ($files as $file) {
            $sourceFile = $file->getPathname();
            $targetFile = "$dst/" . $file->getFilename();
            if ($file->isDir()) {
                $this->copyFolder($sourceFile, $targetFile);
            } else {
                if (!file_exists($targetFile)) {
                    $this->copyFile($sourceFile, $targetFile);
                    \Nette\Diagnostics\FireLogger::log("Resources: new file $targetFile");
                } elseif (filesize($targetFile) != filesize($sourceFile)) {
                    $this->copyFile($sourceFile, $targetFile);
                    \Nette\Diagnostics\FireLogger::log("Resources: modified file $targetFile");
                }
            }
        }

        return true;
    }

    /**
     * Copy file (chunked)
     *
     * @param string $src  source file
     * @param string $dest destination file
     */
    public function copyFile($src, $dest)
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
     * Normalize directory path
     *
     * @param string $path dir path
     *
     * @return string
     */
    private function normalizeDirPath($path)
    {
        $lastChar = substr($path, -1);
        if ($lastChar === "/" || $lastChar === "\\") {
            return substr($path, 0, -1);
        }
        return $path;
    }

}
