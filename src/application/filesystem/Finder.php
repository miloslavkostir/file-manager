<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Application\FileSystem;

/**
 * Extension of Nette\Utils\Finder
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Finder extends \Nette\Utils\Finder
{

    /** @var mixed */
    private $order;

    /**
     * Sets the order comparison function
     *
     * @param callback $order Order callback
     *
     * @return \Ixtrum\FileManager\Application\FileSystem
     */
    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Order by
     *
     * @param string $key Key
     *
     * @return \Ixtrum\FileManager\Application\FileSystem\Finder
     */
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
                        $fileSystem = new \Ixtrum\FileManager\Application\FileSystem;
                        return $fileSystem->getSize($f2->getPathName()) - $fileSystem->getSize($f1->getPathName());
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
     * @return \ArrayIterator
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