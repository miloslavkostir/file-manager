<?php

namespace Ixtrum\FileManager\Application\FileSystem;

/**
 * Workaround for missing sort method in Nette Finder
 *
 * @see http://forum.nette.org/cs/5331-2010-09-15-trida-nette-finder-pro-prochazeni-adresarovou-strukturou
 */
class Finder extends \Nette\Utils\Finder
{

    private $order;

    /**
     * Sets the order comparison function
     * @param callback $order
     * @return Finder
     */
    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    public function orderBy($key)
    {
        switch ($key) {
            case 'name':
                $this->order = function($f1, $f2) {
                            return \strcasecmp($f1->getFilename(), $f2->getFilename());
                        };
                break;
            case 'type':
                $this->order = function($f1, $f2) {
                            return \strcasecmp(
                                            pathinfo($f1->getPathName(), PATHINFO_EXTENSION), pathinfo($f2->getPathName(), PATHINFO_EXTENSION));
                        };
                break;
            case 'time':
                $this->order = function($f1, $f2) {
                            return $f2->getMTime() - $f1->getMTime();
                        };
                break;
            case 'size':
                $this->order = function($f1, $f2) {
                            $fileSystem = new FileSystem;
                            return $fileSystem->filesize($f2->getPathName()) - $fileSystem->filesize($f1->getPathName());
                        };
                break;
            default:
                throw new InvalidArgumentException('Unknown expression, allowed are NAME, TYPE, TIME, SIZE');
        }

        return $this;
    }

    /**
     * Returns iterator.
     * @return \Iterator
     */
    public function getIterator()
    {
        $iterator = parent::getIterator();
        if ($this->order === NULL) {
            return $iterator;
        }

        $iterator = new \ArrayIterator(\iterator_to_array($iterator));
        $iterator->uasort($this->order);

        return $iterator;
    }

}