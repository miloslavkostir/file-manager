<?php

namespace Ixtrum\FileManager\Application;

use Ixtrum\FileManager\Application\FileSystem\Finder,
    Nette\Utils\Json;

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

        // Set default pluginDir
        if (!isset($config["pluginDir"])) {
            $config["pluginDir"] = $config["appDir"] . DIRECTORY_SEPARATOR . "plugins";
        } else {
            $config["pluginDir"] = realpath($config["pluginDir"]);
        }

        // Set default langDir
        if (!isset($config["langDir"])) {
            $config["langDir"] = $config["appDir"] . DIRECTORY_SEPARATOR . "lang";
        } else {
            $config["langDir"] = realpath($config["langDir"]);
        }

        // Get plugins
        $config["plugins"] = $this->getPlugins($config["pluginDir"]);

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

        if (!is_dir($this->parameters["pluginDir"])) {
            throw new \Nette\DirectoryNotFoundException("Plugin dir '" . $this->parameters["pluginDir"] . "' doesn't exist!");
        }

        if (!is_dir($this->parameters["langDir"])) {
            throw new \Nette\DirectoryNotFoundException("Language dir '" . $this->parameters["langDir"] . "' doesn't exist!");
        }

        if ($this->parameters["quota"] && (int) $this->parameters["quotaLimit"] === 0) {
            throw new \Nette\InvalidArgumentException("Quota limit must defined if quota enabled, but '" . $this->parameters["quotaLimit"] . "' given!");
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
        return new Translator($this->parameters["langDir"] . DIRECTORY_SEPARATOR . $this->parameters["lang"] . ".json");
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
        return new FileSystem;
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

    /**
     * Get plugins
     *
     * @param string $pluginDir Plugins directory
     *
     * @return array
     */
    public function getPlugins($pluginDir)
    {
        $plugins = array();
        foreach (Finder::findFiles("plugin.json")->from($pluginDir) as $plugin) {

            $config = Json::decode(file_get_contents($plugin->getRealPath()), 1);
            $config["path"] = dirname($plugin->getRealPath());
            $plugins[$config["name"]] = $config;
        }

        return $plugins;
    }

}