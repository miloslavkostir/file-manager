<?php

namespace Ixtrum\FileManager\Application;

final class Loader extends \Nette\DI\Container
{

    /** @var string */
    private $tempDir;

    /** @var string */
    private $wwwDir;

    /** @var \Nette\Http\Session */
    private $session;

    /**
     * Constructor
     *
     * @param \Nette\Http\Session $session Session
     * @param string              $tempDir Temp directory
     * @param string              $wwwDir  Application WWW directory
     * @param array               $config  Custom configuration
     * @param string              $appPath Path to application componnet itself
     */
    public function __construct(\Nette\Http\Session $session, $tempDir, $wwwDir, $config, $appPath)
    {
        $this->session = $session;
        $this->tempDir = $tempDir;
        $this->wwwDir = $wwwDir;
        $this->parameters = $this->createConfiguration($config, $appPath);
        $this->checkRequirements();
    }

    /**
     * Create application configuration
     *
     * @param array  $config  User configuration
     * @param string $appPath Path to applciation itself
     *
     * @return array Configuration
     */
    private function createConfiguration($config, $appPath)
    {
        // Get default config
        $loader = new \Nette\Config\Loader;
        $defaultConfig = $loader->load("$appPath/config/default.neon");

        // Merge user config with default config
        $config = array_merge($defaultConfig["parameters"], $config);

        // Define system paths
        $config["appPath"] = $appPath;
        $config["wwwDir"] = $this->wwwDir;
        $config["tempDir"] = $this->tempDir;

        // Get plugins
        $plugins = new Plugins($config["appPath"] . $config["pluginDir"], new Caching($config));
        $config["plugins"] = $plugins->loadPlugins();

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
                        $this->parameters["appPath"] . $this->parameters["langDir"] . $this->parameters["lang"] . ".mo",
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