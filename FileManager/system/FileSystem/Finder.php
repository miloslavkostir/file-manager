<?php

namespace Ixtrum\FileManager\Application\FileSystem;

/**
 * Add sorting to Nette\Utils\Finder
 */
class Finder extends \Nette\Utils\Finder
{

    /** @var mixed */
    private $order;

    /**
     * Sets the order comparison function
     *
     * @param callback $order
     *
     * @return \Ixtrum\FileManager\Application\FileSystem
     */
    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    public function orderBy($key)
    {
        switch ($key) {
            case "name":
                $this->order = function($f1, $f2) {
                        return \strcasecmp($f1->getFilename(), $f2->getFilename());
                    };
                break;
            case "time":
                $this->order = function($f1, $f2) {
                        return $f2->getMTime() - $f1->getMTime();
                    };
                break;
            case "size":
                $this->order = function($f1, $f2) {
                        if ($f2->isDir() || $f1->isDir()) {
                            return;
                        }
                        $fileSystem = new \Ixtrum\FileManager\Application\FileSystem(array());
                        return $fileSystem->filesize($f2->getPathName()) - $fileSystem->filesize($f1->getPathName());
                    };
                break;
            default:
                $this->order = function($f1, $f2) {
                        // Default is order by type
                        return \strcasecmp(
                                pathinfo($f1->getPathName(), PATHINFO_EXTENSION), pathinfo($f2->getPathName(), PATHINFO_EXTENSION)
                        );
                    };
        }

        return $this;
    }

    /**
     * Get iterator
     *
     * @return \Iterator
     */
    public function getIterator()
    {
        $iterator = parent::getIterator();
        if ($this->order === null) {
            return $iterator;
        }

        $iterator = new \ArrayIterator(\iterator_to_array($iterator));
        $iterator->uasort($this->order);

        return $iterator;
    }

}