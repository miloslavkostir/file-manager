<?php

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
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
     * Clear cache recursively
     * @param array     dir tree
     * @param string    actualdir
     */
    public function clearDirCache($dirs, $superior)
    {            
            foreach ($dirs as $key => $value) {

                    $cache_const = md5($this->config['uploadroot'] . $this->config['uploadpath']);
                    $cache_dir = parent::getParent()->cache_path . $cache_const;
                    $storage = new FileStorage($cache_dir);
                    $cache = new Cache($storage);
                                       
                    unset($cache[array('fmfiles', $superior . "/" . $key . "/")]);

                    if (count($dirs[$key]) > 0) {
                            $sub_superior = $superior . "/" . $key;
                            $this->clearDirCache($dirs[$key], $sub_superior);
                    }

            }
    }

    /**
     * Clear single item from cache
     * @param mixed string, array
     */
    public function clearFromCache($key)
    {
        $cache_const = md5($this->config['uploadroot'] . $this->config['uploadpath']);
        $cache_dir = parent::getParent()->cache_path . $cache_const;
        $storage = new FileStorage($cache_dir);
        $cache = new Cache($storage);

        unset($cache[$key]);
    }

    /**
     * Delete all data from cache
     */
    public function clearCache()
    {
        $cache_const = md5($this->config['uploadroot'] . $this->config['uploadpath']);
        $cache_dir = parent::getParent()->cache_path . $cache_const;
        $storage = new FileStorage($cache_dir);
        $cache = new Cache($storage);

        $cache->clean(array(NCache::ALL=>true));
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