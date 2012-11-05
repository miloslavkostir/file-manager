<?php

namespace Ixtrum\FileManager\Application;

use Nette\Caching\Cache,
    Nette\Caching\Storages\FileJournal,
    Nette\Caching\Storages\FileStorage,
    Nette\Caching\Storages\MemcachedStorage,
    Nette\Utils\Finder;

class Caching
{

    /** @var Cache */
    private $cache;

    /** @var array */
    private $config;

    /**
     * Constructor
     * 
     * @param array $config application configuration
     */
    public function __construct($config)
    {
        $storageCfg = strtolower($config["cacheStorage"]);
        if ($storageCfg === "filestorage") {

            $cacheDir = $config["tempDir"] . "/cache/_Ixtrum.FileManager";
            if (!is_dir($cacheDir)) {

                $oldumask = umask(0);
                mkdir($cacheDir, 0777);
                umask($oldumask);
            }

            $storage = new FileStorage($cacheDir, new FileJournal($cacheDir));
        } elseif ($storageCfg === "memcachedstorage") {
            $storage = new MemcachedStorage();
        }

        $this->cache = new Cache($storage);
        $this->config = $config;
    }

    /**
     * Delete single item from cache
     *
     * @param mixed $key
     * @param array $conds (optional)
     */
    public function deleteItem($key, $conds = NULL)
    {
        if ($conds) {
            $this->cache->clean($conds);
        } else {
            unset($this->cache[$key]);
        }
    }

    /**
     * Delete items from cache with recursion
     *
     * @param string $absDir
     */
    public function deleteItemsRecursive($absDir)
    {
        $dirs = Finder::findDirectories("*")->from($absDir);
        $cache = $this->cache;
        $fileSystem = new FileSystem($this->config);

        foreach ($dirs as $dir) {

            $key = $fileSystem->getRealPath($dir->getPathName());
            unset($cache[array("content", $key)]);
        }

        unset($cache[array("content", $fileSystem->getRealPath($absDir))]);
        $this->deleteItem(NULL, array("tags" => "treeview"));
    }

    /**
     * Get data from cache storage
     *
     * @param mixed $key
     * @return cache data | NULL
     */
    public function getItem($key)
    {
        $cache = $this->cache;
        if (isset($cache[$key])) {
            return $cache[$key];
        } else {
            return NULL;
        }
    }

    /**
     * Save data to cache storage
     *
     * @param mixed $key
     * @param mixed $value
     * @param array $options
     */
    public function saveItem($key, $value, $options = NULL)
    {
        $this->cache->save($key, $value, $options);
    }

}