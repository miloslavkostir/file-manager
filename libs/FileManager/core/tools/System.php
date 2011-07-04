<?php

class System extends FileManager
{
    /** @var array */
    public $config;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get actual relative dir path
     * @return string
     */
    public function getActualDir()
    {
        return $this->presenter->context->session->getSection('file-manager')->actualdir;
    }

    /**
     * Set actual relative dir path
     * @param string $dir
     */
    public function setActualDir($dir)
    {
        $this->presenter->context->session->getSection('file-manager')->actualdir = $dir;
    }

    /**
     * Check if string is name of valid plugin
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
    public function getTranslator()
    {
        $lang = __DIR__ . '/../../lang/' . $this->config["lang"] . '.mo';
        if (file_exists($lang)) {
            $transl = new GettextTranslator($lang);
            return $transl;
        } else
            throw new Exception("Language file $lang does not exists!");
    }
}