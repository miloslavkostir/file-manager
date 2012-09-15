<?php

namespace Ixtrum\FileManager\Application;

class Clipboard
{

    /** @var \Nette\Http\SessionSection */
    private $sessionSection;

    /**
     * Constructor
     *
     * @param \Nette\Http\SessionSection $sessionSection session section
     */
    public function __construct(\Nette\Http\SessionSection $sessionSection)
    {
        $this->sessionSection = $sessionSection;
    }

    /**
     * Add files/folders to clipboard
     *
     * @param string $key    Key
     * @param array  $values Values
     */
    public function add($key, $values)
    {
        $this->sessionSection->clipboard[$key] = $values;
    }

    /**
     * Remmove item from clipboard
     *
     * @param string $key key
     */
    public function remove($key)
    {
        unset($this->sessionSection->clipboard[$key]);
    }

    /**
     * Clear all items from clipboard
     */
    public function clear()
    {
        unset($this->sessionSection->clipboard);
    }

    /**
     * Get clibpoard with all items
     *
     * @return array
     */
    public function get()
    {
        return $this->sessionSection->clipboard;
    }

}