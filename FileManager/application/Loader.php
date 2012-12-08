<?php

namespace Ixtrum\FileManager\Application;

final class Loader extends \Nette\DI\Container
{

    /** @var \Nette\Http\Session */
    private $session;

    /**
     * Constructor
     *
     * @param \Nette\Http\Session $session Session
     * @param array               $config  Custom configuration
     */
    public function __construct(\Nette\Http\Session $session, $config)
    {
        $this->session = $session;
        $this->parameters = $this->createConfiguration($config);
        $this->checkRequirements();
    }

    /**
     * Create application configuration
     *
     * @param array  $config  User configuration
     *
     * @return array Configuration
     */
    private function createConfiguration($config)
    {
        // Get default config
        $loader = new \Nette\Config\Loader;
        $defaultConfig = $loader->load($config["appDir"] . "/config/default.neon");

        // Merge user config with default config
        $config = array_merge($defaultConfig["parameters"], $config);

        // Get plugins
        $plugins = new Plugins($config["appDir"] . $config["pluginDir"], new Caching($config));
        $config["plugins"] = $plugins->loadPlugins();

        // Canonicalize uploadroot
        $config["uploadroot"] = realpath($config["uploadroot"]);

        return $config;
    }

    /**
     * Check application requirements
     *
     * @throws \Nette\DirectoryNotFoundException
     * @throws \Nette\Application\ApplicationException
     */
    private function checkRequirements()
    {
        if (!isset($this->parameters["uploadroot"]) || empty($this->parameters["uploadroot"])) {
            throw new \Nette\InvalidArgumentException("Parameter 'uploadroot' not defined!");
        }

        if (!is_dir($this->parameters["uploadroot"])) {
            throw new \Nette\DirectoryNotFoundException("Upload root '" . $this->parameters["uploadroot"] . "' doesn't exist!");
        }
    }

    /**
     * Create service caching
     *
     * @return \Ixtrum\FileManager\Application\Caching
     */
    protected function createServiceCaching()
    {
        return new Caching($this->parameters);
    }

    /**
     * Create service translator
     *
     * @return \Ixtrum\FileManager\Application\Translator\GettextTranslator
     */
    protected function createServiceTranslator()
    {
        return new Translator\GettextTranslator(
                        $this->parameters["appDir"] . $this->parameters["langDir"] . $this->parameters["lang"] . ".mo",
                        new Caching($this->parameters),
                        $this->parameters["lang"]
        );
    }

    /**
     * Create service session
     *
     * @return \Ixtrum\FileManager\Application\Session
     */
    protected function createServiceSession()
    {
        return new Session($this->session->getSection("file-manager"));
    }

    /**
     * Create service fileSystem
     *
     * @return \Ixtrum\FileManager\Application\FileSystem
     */
    protected function createServiceFilesystem()
    {
        return new FileSystem($this->parameters);
    }

    /**
     * Create service thumbs
     *
     * @return \Ixtrum\FileManager\Application\Thumbs
     */
    protected function createServiceThumbs()
    {
        return new Thumbs($this->parameters);
    }

}