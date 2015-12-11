<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Application;

use Nette\Utils\Json,
    Ixtrum\FileManager\Application\FileSystem\Finder;

/**
 * Configurator.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Configurator
{

    /** @var array */
    private $defaults = array(
        "appDir" => null,
        "dataDir" => null,
		"hiddenDirs" => null,
        "cache" => true,
        "cacheDir" => null,
        "cacheStorage" => "FileStorage",
        "thumbs" => true,
        "thumbsDir" => null,
        "resUrl" => "ixtrum-res",
        "readonly" => false,
        "quota" => false,
        "quotaLimit" => 20, // megabytes
        "lang" => "en",
        "langs" => array(),
        "langDir" => null,
        "plugins" => array(),
        "pluginsDir" => null
    );

    /**
     * Create application configuration
     *
     * @param array $custom Custom configuration
     *
     * @return array Configuration
     *
     * @throws \Exception
     */
    public function createConfig(array $custom)
    {
        if (!isset($custom["dataDir"])) {
            throw new \Exception("Parameter 'dataDir' not defined!");
        }

        // Merge custom config with default config
        $config = array_merge($this->createDefaults(), $custom);

        // Check required parameters
        if ($config["quota"] && (int) $config["quotaLimit"] <= 0) {
            throw new \Exception("Quota limit must defined if quota enabled, but '" . $config["quotaLimit"] . "' given!");
        }
        if (!is_dir($config["dataDir"])) {
            throw new \Exception("Data directory '" . $config["dataDir"] . "' not found!");
        }

        // Canonicalize dataDir
        $config["dataDir"] = realpath($config["dataDir"]);

        // Define absolute path to resources
        $config["resDir"] = dirname($_SERVER["SCRIPT_FILENAME"]) . "/" . $config["resUrl"];

        // Get available plugins
        $config["plugins"] = $this->getPlugins($config["pluginsDir"]);

        // Get available languages
        $config["langs"] = $this->getLanguages($config["langDir"]);

        return $config;
    }

    /**
     * Get plugins
     *
     * @param string $pluginsDir Plugins directory
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getPlugins($pluginsDir)
    {
        if (!is_dir($pluginsDir)) {
            throw new \Exception("Plugins directory '$pluginsDir' not found!");
        }

        $plugins = array();
        foreach (Finder::findFiles("plugin.json")->from($pluginsDir) as $plugin) {

            $config = Json::decode(file_get_contents($plugin->getRealPath()), 1);
            $config["path"] = dirname($plugin->getRealPath());
            $plugins[$config["name"]] = $config;
        }

        return $plugins;
    }

    /**
     * Create default configuration
     *
     * @return array
     */
    private function createDefaults()
    {
        $reflection = new \ReflectionClass("\Ixtrum\FileManager");
        $appDir = dirname($reflection->getFileName());

        // Get and complete default system parameters
        $defaults = $this->defaults;
        $defaults["pluginsDir"] = "$appDir/plugins";
        $defaults["langDir"] = "$appDir/lang";

        return $defaults;
    }

    /**
     * Get available languages
     *
     * @todo Get lanugage title too
     *
     * @return array
     */
    private function getLanguages($langDir)
    {
        $langs = array($this->defaults["lang"] => $this->defaults["lang"]);
        foreach (Finder::findFiles("*.json")->in($langDir) as $file) {
            $langs[$file->getBasename(".json")] = $file->getBasename(".json");
        }
        return $langs;
    }

}