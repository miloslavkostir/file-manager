<?php

namespace Ixtrum\FileManager;

class Application
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
     * Get actual relative dir path
     *
     * @return string
     */
    public function getActualDir()
    {
        return $this->sessionSection->actualdir;
    }

    /**
     * Set actual relative dir path
     *
     * @param string $dir
     */
    public function setActualDir($dir)
    {
        $this->sessionSection->actualdir = $dir;
    }

}