<?php

class System extends FileManager
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get actual relative dir path
     * 
     * @return string
     */
    public function getActualDir()
    {
        return $this->presenter->context->session->getNamespace('file-manager')->actualdir;
    }

    /**
     * Set actual relative dir path
     * 
     * @param string $dir
     */
    public function setActualDir($dir)
    {
        $this->presenter->context->session->getNamespace('file-manager')->actualdir = $dir;
    }
}