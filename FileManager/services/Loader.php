<?php

namespace Ixtrum\FileManager\Services;

use Nette\DirectoryNotFoundException,
    Nette\Application\ApplicationException,
    Ixtrum\FileManager\Application;

final class Loader extends \Nette\DI\Container
{

    /** @var \Nette\DI\Container System container */
    private $context;

    public function __construct(\Nette\DI\Container $container, $config, $rootPath)
    {
        $loader = new \Nette\Config\Loader;
        $config = $loader->load("$rootPath/config/config.neon");
        array_merge($config["parameters"], $config);
        $config["parameters"]["rootPath"] = $rootPath;
        $config["parameters"]["resPath"] = $container->parameters["wwwDir"] . $config["parameters"]["resDir"];

        if (!isset($config["parameters"]["uploadroot"])) {
            $config["parameters"]["uploadroot"] = $rootPath;
        }

        // Merge plugins with configuration
        $plugins = new Application\Plugins(
                $config["parameters"]["rootPath"] . $config["parameters"]["pluginDir"],
                new Application\Caching($container, $config["parameters"])
        );
        $config["parameters"]["plugins"] = $plugins->loadPlugins();

        $this->parameters = $config["parameters"];
        $this->context = $container;

        $this->init();
    }

    protected function createServiceSystemContainer()
    {
        return $this->context;
    }

    protected function createServiceCaching()
    {
        return new Application\Caching($this->context, $this->parameters);
    }

    protected function createServiceTranslator()
    {
        $translator = new Application\Translator\GettextTranslator(
                $this->parameters["rootPath"] . $this->parameters["langDir"] . $this->parameters["lang"] . ".mo",
                new Application\Caching($this->context, $this->parameters),
                $this->parameters["lang"]
        );
        return $translator;
    }

    protected function createServiceApplication()
    {
        return new Application($this->context->session);
    }

    protected function createServiceFilesystem()
    {
        return new Application\FileSystem($this->context, $this->parameters);
    }

    protected function createServiceThumbs()
    {
        return new Application\Thumbs($this->context, $this->parameters);
    }

    private function init()
    {
        $uploadPath = $this->parameters["uploadroot"] . $this->parameters["uploadpath"];
        if (!is_dir($uploadPath)) {
            throw new DirectoryNotFoundException("Upload path '$uploadPath' doesn't exist!");
        }

        if (!is_writable($uploadPath)) {
            throw new ApplicationException("Upload path '$uploadPath' must be writable!");
        }

        if (!is_dir($uploadPath)) {
            throw new DirectoryNotFoundException("Resource path '$uploadPath' doesn't exist!");
        }

        $tempDir = $this->context->parameters["tempDir"] . "/file-manager";
        if (!is_dir($tempDir)) {

            $oldumask = umask(0);
            mkdir($tempDir, 0777);
            umask($oldumask);
        }

        if (!is_writable($tempDir)) {
            throw new ApplicationException("Temp dir '$tempDir' must be writable!");
        }
    }

}