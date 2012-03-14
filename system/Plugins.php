<?php

namespace Ixtrum\FileManager\System;

class Plugins
{
        /** @var string */
        private $pluginDir;

        /** @var Caching */
        private $cache;

        public function __construct($pluginDir, Caching $cache)
        {
                if (is_dir($pluginDir))
                        $this->pluginDir = $pluginDir;
                else
                        throw new \Nette\DirectoryNotFoundException("Plugin directory '$pluginDir' does not exists!");

                $this->cache = $cache;
        }


        /**
         * Get plugins
         * 
         * @param Caching
         * @return array
         */
        public function loadPlugins()
        {
                $files = \Nette\Utils\Finder::findFiles("*.php")
                                ->from($this->pluginDir);

                $cache = $this->cache;
                $plugins = array();

                foreach ( $files as $file ) {

                        $filePath = $file->getRealPath();
                        $cacheData = $cache->getItem(array("plugins", $filePath));

                        if ($cacheData)
                                $plugins[$filePath] = $cacheData;
                        else {

                                $php_code = file_get_contents($filePath);
                                $classes = $this->get_php_classes($php_code);
                                $class = $classes[0];

                                $vars = get_class_vars("\Ixtrum\FileManager\Plugins\\$class");

                                $plugins[$filePath]["name"] = $class;
                                $plugins[$filePath]["title"] = $vars["title"];
                                $plugins[$filePath]["toolbarPlugin"] = $vars["toolbarPlugin"];
                                $plugins[$filePath]["contextPlugin"] = $vars["contextPlugin"];

                                $cache->saveItem(array("plugins", $filePath), $plugins[$filePath], array(\Nette\Caching\Cache::FILES => $filePath));
                        }
                }

                return $plugins;
        }


        /**
         * Get list of plugin names
         * 
         * @return array
         */
        public function getPluginNames()
        {
                $plugins = $this->loadPlugins();
                $names = array();

                foreach ($plugins as $plugin) {
                    $names[] = $plugin["name"];
                }

                return $names;
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

                        if (   $tokens[$i - 2][0] == T_CLASS
                            && $tokens[$i - 1][0] == T_WHITESPACE
                            && $tokens[$i][0] == T_STRING) {

                                    $class_name = $tokens[$i][1];
                                    $classes[] = $class_name;
                        }
                }

                return $classes;
        }


        /**
         * Check if string is name of valid plugin or system control
         * 
         * @param string $name
         * @return bool
         */
        public function isValidControl($name)
        {
                $systemControls = array("NewFolder", "rename");
                if (in_array($name, $this->getPluginNames()) || in_array($name, $systemControls))
                        return true;

                return false;
        }
}