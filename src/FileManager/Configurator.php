<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager;

use Nette\Utils\Json,
    Ixtrum\FileManager\FileSystem\Finder;

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
        "pluginsDirs" => array()
    );

    /** @var array */
    private $pluginTypes = array(
        "Ixtrum\FileManager\Plugins\IAddressbar" => "addressbar",
        "Ixtrum\FileManager\Plugins\IContent" => "content",
        "Ixtrum\FileManager\Plugins\IContextMenu" => "contextmenu",
        "Ixtrum\FileManager\Plugins\IInfobar" => "infobar",
        "Ixtrum\FileManager\Plugins\IToolbar" => "toolbar"
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
        if ($config["pluginsDirs"]) {
            $config["plugins"] = $this->getPlugins($config["pluginsDirs"]);
        }

        // Get available languages
        $config["langs"] = $this->getLanguages($config["langDir"]);

        return $config;
    }

    /**
     * Get plugins
     *
     * @param array $pluginsDirs Plugins directories
     *
     * @return array
     */
    private function getPlugins(array $pluginsDirs)
    {
        $plugins = array();
        foreach (Finder::findFiles("plugin.json")->from($pluginsDirs) as $plugin) {

            $config = Json::decode(file_get_contents($plugin->getRealPath()), 1);
            if (!isset($config["class"]) || !class_exists($config["class"])) {
                continue;
            }

            // Get types
            foreach (class_implements($config["class"]) as $interface) {

                if (isset($this->pluginTypes[$interface])) {
                    $config["types"][$this->pluginTypes[$interface]] = true;
                }
            }

            // Skip if unknown plugin type
            if (empty($config["types"])) {
                continue;
            }

            // Get plugin dir path
            $config["path"] = dirname($plugin->getPathName());

            // Get unique plugin name from class
            $config["name"] = strtolower(stripslashes($config["class"]));
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
        $defaults["appDir"] = $appDir;
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