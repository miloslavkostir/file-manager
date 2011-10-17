<?php

namespace Netfileman;

use Nette\Utils\Finder;

class Tools extends FileManager
{
    /** @var array */
    public $config;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get absolute path from relative path
     * @param string $actualdir
     * @return string
     */
    function getAbsolutePath($actualdir)
    {
        if ($actualdir == $this->getRootname())
            return $this->config['uploadroot'] . $this->config['uploadpath'];
        else
            return $this->config['uploadroot'] . substr($this->config['uploadpath'], 0, -1) . $actualdir;
    }

    /**
     * Repair (back)slashes according to OS
     * @param string $path
     * @return string
     */
    function getRealPath($path)
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));

        if ($os === 'WIN')
            $path = str_replace('/', '\\', $path);
        else
            $path = str_replace('\\', '/', $path);


        if (realpath($path)) {
                $path = realpath($path);
                if (is_dir($path)) {
                    if ($os === 'WIN' && substr($path, -1) <> '\\')
                            $path .= '\\';
                    if ($os <> 'WIN' && substr($path, -1) <> '/')
                            $path .= '/';
                }

                return $path;
        } else
                throw new \Nette\InvalidArgumentException("Invalid path $path given!");
    }

    /**
     * Get root folder name
     * @return string
     */
    function getRootname()
    {
        $path = $this->config['uploadpath'];
        $first = substr($path, 0, 1);
        $last = substr($path, -1, 1);

        if ( ($first === '/' || $first === '\\') && ($last === '/' || $last === '\\'))
            $path = substr($path, 1, strlen($path) - 2);
        else
            throw new \Nette\InvalidArgumentException("Invalid upload path '$path' given! Correct path starts & ends with \ (Windows) or / (Unix).");

        return $path;
    }

    /**
     * Get used disk space
     * @return integer bytes
     */
    public function getUsedSize()
    {
        $size = 0;
        foreach (Finder::findFiles('*')->from($this->config['uploadroot'] . $this->config['uploadpath']) as $file) {
                           $size += $this['files']->getFileSize($file->getPathName());
        }
        return $size;
    }

    /**
     * Get details about used disk size
     * @return array
     */
    public function diskSizeInfo()
    {
        $info = array();

        if ($this->config['quota'] == True) {
            $size = $this->getUsedSize();
            $info['usedsize'] = $size;
            $info['spaceleft'] = ($this->config['quota_limit'] * 1048576) - $size;
            $info['percentused'] = round(($size / ($this->config['quota_limit'] * 1048576)) * 100);
        } else {
            $path = $this->config['uploadroot'] . $this->config['uploadpath'];
            $freesize = disk_free_space($path);
            $totalsize = disk_total_space($path);
            $info['usedsize'] = $totalsize - $freesize;
            $info['spaceleft'] = $freesize;
            $info['percentused'] = round(($info['usedsize'] / $totalsize ) * 100);
        }

        return $info;
    }

    /**
     * Check if realtive path is valid
     * @param string $dir
     * @param string $file (optional)
     * @return bool
     */
    public function validPath($dir, $file = NULL)
    {
        $path = $this['tools']->getAbsolutePath($dir);

        if (!empty($file))
            $path .= $file;

        if (file_exists($path))
            return True;
        else {
            $translator = $this['system']->getTranslator();
            parent::getParent()->flashMessage(
                $translator->translate('Target path %s not found!', $dir),
                'warning'
            );
            parent::getParent()->invalidateControl('message');
            return False;
        }
    }
}