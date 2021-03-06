<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager;

/**
 * Cache wrapper.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Caching
{

    /** @var \Nette\Caching\Cache */
    private $cache;

    /**
     * Constructor
     *
     * @param \Nette\Caching\IStorage $storage Cache storage
     *
     * @throws \Exception
     */
    public function __construct(\Nette\Caching\IStorage $storage)
    {
        $this->cache = new \Nette\Caching\Cache($storage, "Ixtrum.FileManager");
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
     * @param string $absDir Absolute directory path
     */
    public function deleteItemsRecursive($absDir)
    {
        $dirs = FileSystem\Finder::findDirectories("*")->from($absDir);
        $cache = $this->cache;
        foreach ($dirs as $dir) {
            unset($cache[array("content", $dir->getRealPath())]);
        }
        unset($cache[array("content", $absDir)]);
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