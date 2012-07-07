<?php

namespace Ixtrum\FileManager\Application;

use Nette\Caching\Cache,
    Nette\Caching\Storages\FileJournal,
    Nette\Caching\Storages\FileStorage,
    Nette\Caching\Storages\MemcachedStorage,
    Nette\Utils\Finder,
    Nette\DI\Container;

class Caching
{

    /** @var Cache */
    private $cache;

    /** @var Container */
    private $context;

    /** @var array */
    private $config;

    public function __construct(Container $container, array $config)
    {
        $storageCfg = strtolower($config["cacheStorage"]);
        if ($storageCfg === "filestorage") {
            $tempDir = $container->parameters["tempDir"];
            $cacheDir = "$tempDir/file-manager/cache";

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
        $this->context = $container;
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
        $fileSystem = new FileSystem($this->context, $this->config);

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