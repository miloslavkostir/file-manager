<?php

namespace Ixtrum\FileManager;

class Application
{

    /** @var \Nette\Http\Session */
    private $session;

    public function __construct(\Nette\Http\Session $session)
    {
        $this->session = $session;
    }

    /**
     * Get actual relative dir path
     *
     * @return string
     */
    public function getActualDir()
    {
        return $this->session->getSection("file-manager")->actualdir;
    }

    /**
     * Set actual relative dir path
     *
     * @param string $dir
     */
    public function setActualDir($dir)
    {
        $this->session->getSection("file-manager")->actualdir = $dir;
    }

    /**
     * Clear all items in clipboard
     */
    public function clearClipboard()
    {
        $session = $this->session->getSection("file-manager");
        unset($session->clipboard);
    }

}