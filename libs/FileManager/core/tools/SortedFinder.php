<?php

/**
 * THX pracj3am
 * http://forum.nette.org/cs/5331-2010-09-15-trida-nette-finder-pro-prochazeni-adresarovou-strukturou
 *
 * TODO workaround for missing sort method in Nette Finder
 * 
 */

use Nette\Utils\Finder;

class SortedFinder extends Finder
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


    public function orderByName()
    {
        $this->order = function($f1, $f2) {
            return \strcasecmp($f1->getFilename(), $f2->getFilename());
        };
        return $this;
    }

    public function orderByType()
    {
        $this->order = function($f1, $f2) {
            return \strcasecmp(
                    pathinfo($f1->getPathName(), PATHINFO_EXTENSION),
                    pathinfo($f2->getPathName(), PATHINFO_EXTENSION));
        };
        return $this;
    }


    public function orderByMTime()
    {
        $this->order = function($f1, $f2) {
            return $f2->getMTime() - $f2->getMTime();
        };
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