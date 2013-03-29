<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Application;

use Nette\Caching\Cache,
    Nette\Caching\Storages\FileJournal,
    Nette\Caching\Storages\FileStorage,
    Nette\Caching\Storages\MemcachedStorage,
    Ixtrum\FileManager\Application\FileSystem\Finder;

/**
 * Cache wrapper.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Caching
{

    /** @var \Nette\Caching\Cache */
    private $cache;

    /** @var array */
    private $config;

    /**
     * Constructor
     *
     * @param array $config Configuration
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
     * @param mixed $key   key
     * @param array $conds Conditions (optional)
     */
    public function deleteItem($key, $conds = null)
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
     * @param string $absDir Absolute dir path
     */
    public function deleteItemsRecursive($absDir)
    {
        $dirs = Finder::findDirectories("*")->from($absDir);
        $cache = $this->cache;
        foreach ($dirs as $dir) {
            unset($cache[array("content", $dir->getRealPath())]);
        }
        unset($cache[array("content", $absDir)]);
        $this->deleteItem(null, array("tags" => "treeview"));
    }

    /**
     * Get data from cache storage
     *
     * @param mixed $key Key
     *
     * @return mixed | null Cache data
     */
    public function getItem($key)
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        return null;
    }

    /**
     * Save data to cache storage
     *
     * @param mixed $key     Key
     * @param mixed $value   Value
     * @param array $options Options (optional)
     */
    public function saveItem($key, $value, $options = null)
    {
        $this->cache->save($key, $value, $options);
    }

}