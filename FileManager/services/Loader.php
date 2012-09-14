<?php

namespace Ixtrum\FileManager\Services;

final class Loader extends \Nette\DI\Container
{

    /** @var \Nette\DI\Container System container */
    private $context;

    /**
     * Constructor
     *
     * @param \Nette\DI\Container $systemContainer System container
     * @param array               $config          Custom configuration
     * @param string              $rootPath        Path to application
     */
    public function __construct(\Nette\DI\Container $systemContainer, $config, $rootPath)
    {
        $loader = new \Nette\Config\Loader;
        $config = $loader->load("$rootPath/config/config.neon");
        array_merge($config["parameters"], $config);
        $config["parameters"]["rootPath"] = $rootPath;
        $config["parameters"]["wwwDir"] = $systemContainer->parameters["wwwDir"];
        $config["parameters"]["tempDir"] = $systemContainer->parameters["tempDir"];

        if (!isset($config["parameters"]["uploadroot"])) {
            $config["parameters"]["uploadroot"] = $rootPath;
        }

        // Merge plugins with configuration
        $plugins = new \Ixtrum\FileManager\Application\Plugins(
                $config["parameters"]["rootPath"] . $config["parameters"]["pluginDir"],
                new \Ixtrum\FileManager\Application\Caching($config["parameters"])
        );
        $config["parameters"]["plugins"] = $plugins->loadPlugins();

        $this->parameters = $config["parameters"];
        $this->context = $systemContainer;

        $this->checkRequirements();
    }

    /**
     * Check application requirements
     *
     * @throws \Nette\DirectoryNotFoundException
     * @throws \Nette\Application\ApplicationException
     */
    private function checkRequirements()
    {
        $uploadPath = $this->parameters["uploadroot"] . $this->parameters["uploadpath"];
        if (!is_dir($uploadPath)) {
            throw new \Nette\DirectoryNotFoundException("Upload path '$uploadPath' doesn't exist!");
        }

        if (!is_writable($uploadPath)) {
            throw new \Nette\Application\ApplicationException("Upload path '$uploadPath' must be writable!");
        }

        if (!is_dir($uploadPath)) {
            throw new \Nette\DirectoryNotFoundException("Resource path '$uploadPath' doesn't exist!");
        }
    }

    /**
     * Create service systemContainer
     *
     * @return \Nette\DI\Container
     */
    protected function createServiceSystemContainer()
    {
        return $this->context;
    }

    /**
     * Create service caching
     *
     * @return \Ixtrum\FileManager\Application\Caching
     */
    protected function createServiceCaching()
    {
        return new \Ixtrum\FileManager\Application\Caching($this->parameters);
    }

    /**
     * Create service translator
     *
     * @return \Ixtrum\FileManager\Application\Translator\GettextTranslator
     */
    protected function createServiceTranslator()
    {
        return new \Ixtrum\FileManager\Application\Translator\GettextTranslator(
                $this->parameters["rootPath"] . $this->parameters["langDir"] . $this->parameters["lang"] . ".mo",
                new \Ixtrum\FileManager\Application\Caching($this->parameters),
                $this->parameters["lang"]
        );
    }

    /**
     * Create service application
     *
     * @return \Ixtrum\FileManager\Application\Translator\GettextTranslator
     */
    protected function createServiceApplication()
    {
        return new \Ixtrum\FileManager\Application($this->context->session);
    }

    /**
     * Create service fileSystem
     *
     * @return \Ixtrum\FileManager\Application\FileSystem
     */
    protected function createServiceFilesystem()
    {
        return new \Ixtrum\FileManager\Application\FileSystem($this->parameters);
    }

    /**
     * Create service thumbs
     *
     * @return \Ixtrum\FileManager\Application\Thumbs
     */
    protected function createServiceThumbs()
    {
        return new \Ixtrum\FileManager\Application\Thumbs($this->parameters);
    }

}