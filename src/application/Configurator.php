<?php

namespace Ixtrum\FileManager\Application;

use Nette\Utils\Json,
    Ixtrum\FileManager\Application\FileSystem\Finder,
    Ixtrum\FileManager;

class Configurator
{

    /**
     * Check application requirements with given config
     *
     * @param array $config Configuration
     *
     * @throws \Nette\DirectoryNotFoundException
     * @throws \Nette\InvalidArgumentException
     */
    public function checkRequirements(array $config)
    {
        if (!isset($config["uploadroot"]) || empty($config["uploadroot"])) {
            throw new \Nette\InvalidArgumentException("Parameter 'uploadroot' not defined!");
        }

        if (!is_dir($config["uploadroot"])) {
            throw new \Nette\DirectoryNotFoundException("Upload root '" . $config["uploadroot"] . "' doesn't exist!");
        }

        if (!is_dir($config["pluginDir"])) {
            throw new \Nette\DirectoryNotFoundException("Plugin dir '" . $config["pluginDir"] . "' doesn't exist!");
        }

        if (!is_dir($config["langDir"])) {
            throw new \Nette\DirectoryNotFoundException("Language dir '" . $config["langDir"] . "' doesn't exist!");
        }

        if ($config["quota"] && (int) $config["quotaLimit"] === 0) {
            throw new \Nette\InvalidArgumentException("Quota limit must defined if quota enabled, but '" . $config["quotaLimit"] . "' given!");
        }
    }

    /**
     * Create application configuration
     *
     * @param array  $config  User configuration
     *
     * @return array Configuration
     */
    public function createConfiguration($config)
    {
        // Merge user config with default config
        $config = array_merge(FileManager::getDefaults(), $config);

        // Check requirements
        $this->checkRequirements($config);

        // Canonicalize uploadroot
        $config["uploadroot"] = realpath($config["uploadroot"]);

        // Get plugins
        $config["plugins"] = $this->getPlugins($config["pluginDir"]);

        return $config;
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