<?php

class Plugins extends FileManager
{
    /** @var string */
    protected $pluginDir;

    function __construct()
    {
        $pluginDir = __DIR__ . '/../../plugins/';

        if (@is_dir($pluginDir))
            $this->pluginDir = $pluginDir;
        else
            throw new Exception("Plugin directory $pluginDir does not exists!");
    }

    /**
     * Get plugins
     * @return array
     */
    function loadPlugins()
    {
        $files = Nette\Utils\Finder::findFiles('*.php')
                    ->from($this->pluginDir);

        $plugins = array();

        foreach( $files as $file )
        {
            $php_code = file_get_contents($file->getPathName());
            $classes = $this->get_php_classes($php_code);
            $class = $classes[0];

            $vars = get_class_vars($class);
            $plugins[$class]['name'] = $class;
            $plugins[$class]['title'] = $vars['title'];
            $plugins[$class]['toolbar'] = $vars['toolbar'];
            $plugins[$class]['context'] = $vars['context'];
        }

        return $plugins;
    }

    /**
     * Get classes from PHP code
     * @param string $php_code
     * @return array
     */
    function get_php_classes($php_code) {
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
}