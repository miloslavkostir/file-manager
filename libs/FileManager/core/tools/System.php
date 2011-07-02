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
        return $this->presenter->context->session->getSection('file-manager')->actualdir;
    }

    /**
     * Set actual relative dir path
     * 
     * @param string $dir
     */
    public function setActualDir($dir)
    {
        $this->presenter->context->session->getSection('file-manager')->actualdir = $dir;
    }

    /**
     * Check if string is name of valid plugin
     * 
     * @param array $plugins
     * @param string $name
     * @return bool
     */
    public function isPlugin($plugins, $name)
    {
        foreach ($plugins as $plugin) {
            if ($plugin['name'] === $name)
                return true;
        }

        return false;
    }
}