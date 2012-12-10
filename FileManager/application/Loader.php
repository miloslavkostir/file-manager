<?php

namespace Ixtrum\FileManager\Application;

use Ixtrum\FileManager\Application\FileSystem\Finder;

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

        // Synchronize resources if enabled
        if ($this->parameters["syncResDir"]) {
            $this->syncResources(
                    $this->parameters["wwwDir"] . $this->parameters["resDir"], $this->parameters["appDir"]
            );
        }
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
        $config["plugins"] = $this->getPlugins($config["appDir"] . $config["pluginDir"], new Caching($config));

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

    /**
     * Get plugins
     *
     * @param string                                  $pluginDir Plugins directory
     * @param \Ixtrum\FileManager\Application\Caching $cache     Cache object
     *
     * @return array
     */
    public function getPlugins($pluginDir, \Ixtrum\FileManager\Application\Caching $cache)
    {
        $files = Finder::findFiles("*.php")->from($pluginDir);
        $plugins = array();

        foreach ($files as $file) {

            $filePath = $file->getRealPath();
            $cacheData = $cache->getItem(array("plugins", $filePath));

            if ($cacheData) {
                $plugins[$filePath] = $cacheData;
            } else {

                $php_code = file_get_contents($filePath);
                $classes = $this->get_php_classes($php_code);
                $class = $classes[0];

                $vars = get_class_vars("\Ixtrum\FileManager\Application\Plugins\\$class");

                $plugins[$filePath]["name"] = $class;
                $plugins[$filePath]["title"] = $vars["title"];
                $plugins[$filePath]["toolbarPlugin"] = false;
                $plugins[$filePath]["contextPlugin"] = false;
                $plugins[$filePath]["fileInfoPlugin"] = false;

                if (isset($vars["toolbarPlugin"])) {
                    $plugins[$filePath]["toolbarPlugin"] = $vars["toolbarPlugin"];
                }
                if (isset($vars["contextPlugin"])) {
                    $plugins[$filePath]["contextPlugin"] = $vars["contextPlugin"];
                }
                if (isset($vars["fileInfoPlugin"])) {
                    $plugins[$filePath]["fileInfoPlugin"] = $vars["fileInfoPlugin"];
                }

                $cache->saveItem(array("plugins", $filePath), $plugins[$filePath], array(\Nette\Caching\Cache::FILES => $filePath));
            }
        }

        return $plugins;
    }

    /**
     * Get classes from PHP code
     *
     * @internal
     * @param string $php_code
     * @return array
     */
    private function get_php_classes($php_code)
    {
        $classes = array();
        $tokens = token_get_all($php_code);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {

            if ($tokens[$i - 2][0] == T_CLASS
                    && $tokens[$i - 1][0] == T_WHITESPACE
                    && $tokens[$i][0] == T_STRING) {

                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }

        return $classes;
    }

    /**
     * Synchronize resources
     *
     * @param string $resPath Absolute path to resources, must be located in
     *                        public document root.
     * @param string $appDir  Path to file manager root
     */
    public function syncResources($resPath, $appDir)
    {
        $resources = new Resources($resPath, $appDir);
        $resources->synchronize();
    }

}
