<?php

namespace Netfileman;

use Nette\Caching\Cache,
        Nette\Caching\Storages\FileJournal,
        Nette\Caching\Storages\FileStorage,
        Nette\Utils\Finder;

class Caching extends FileManager
{
    /** @var object */
    private $cache;

    public function __construct()
    {
        parent::__construct();
        $this->monitor('\Nette\Application\UI\Presenter');
    }

    protected function attached($presenter)
    {
        if ($presenter instanceof \Nette\Application\UI\Presenter) {
                $cacheDir = $this->presenter->context->parameters['tempDir'] . '/file-manager';

                if(!is_dir($cacheDir)) {
                    $oldumask = umask(0);
                    mkdir($cacheDir, 0777);
                    umask($oldumask);
                }

                $storage = new FileStorage($cacheDir, new FileJournal($cacheDir));
                $this->cache = new Cache($storage);
        }
        parent::attached($presenter);
    }

    /**
     * Delete single item from cache
     * @param mixed $key
     * @param array $conds (optional)
     */
    public function deleteItem($key, $conds = NULL)
    {
        if (!empty($conds))
            $this->cache->clean($conds);
        else
            unset($this->cache[$key]);
    }

    /**
     * Delete items from cache with recursion
     * @param string $absDir
     */
    public function deleteItemsRecursive($absDir)
    {
        $cache = $this->cache;

        $dirs = Finder::findDirectories('*')
                ->from($absDir)
                ->exclude(parent::getParent()->thumb . '*');

        foreach ( $dirs as $dir ) {
            $key = $this['tools']->getRealPath($dir->getPathName());
            unset($cache[array('content', $key)]);
        }

        unset($cache[array('content', $this['tools']->getRealPath($absDir))]);
        $this->deleteItem(NULL, array('tags' => 'treeview'));
    }

    /**
     * Get data from cache storage
     * @param mixed $key
     * @return mixed
     */
    public function getItem($key)
    {
        $cache = $this->cache;

        if (isset($cache[$key]))
            return $cache[$key];
        else
            return NULL;
    }

    /**
     * Save data to cache storage
     * @param mixed $key
     * @param mixed $value
     * @param array $options
     */
    public function saveItem($key, $value, $options = NULL)
    {
        $this->cache->save($key, $value, $options);
    }
}