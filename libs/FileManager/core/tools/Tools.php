<?php

use Nette\Utils\Finder;

class Tools extends FileManager
{
    /** @var array */
    public $config;
    
    public function __construct()
    {
        parent::__construct();
    }

    public function getUsedSize()
    {
        $size = 0;
        foreach (Finder::findFiles('*')->from($this->config['uploadroot'] . $this->config['uploadpath']) as $file) {
                           $size += $file->getSize();
        }
        return $size;
    }

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

    public function validPath($dir, $file = NULL)
    {
        $path = parent::getParent()->getAbsolutePath($dir);

        if (!empty($file))
            $path .= $file;

        if (file_exists($path))
            return True;
        else {
            $translator = parent::getParent()->getTranslator();
            parent::getParent()->flashMessage(
                $translator->translate('Target path %s not found!', $dir),
                'warning'
            );
            parent::getParent()->invalidateControl('message');
            return False;
        }
    }
}