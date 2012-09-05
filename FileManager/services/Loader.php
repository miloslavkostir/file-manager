<?php

namespace Ixtrum\FileManager\Services;

use Nette\DI\Container,
    Nette\DirectoryNotFoundException,
    Nette\Application\ApplicationException,
    Ixtrum\FileManager\Application;

final class Loader extends Container
{

    /** @var Container */
    private $container;

    public function __construct(Container $container, $config, $rootPath)
    {
        $loader = new \Nette\Config\Loader;
        $config = $loader->load("$rootPath/config/config.neon");
        $config["parameters"] = array_merge($config["parameters"], $config);
        $config["parameters"]["rootPath"] = $rootPath;
        $config["parameters"]["resPath"] = $container->parameters["wwwDir"] . $config["parameters"]["resDir"];

        if (!isset($config["parameters"]["uploadroot"])) {
            $config["parameters"]["uploadroot"] = $rootPath;
        }

        $this->parameters = $config["parameters"];
        $this->container = $container;

        $this->init();
    }

    protected function createServiceCaching()
    {
        return new Application\Caching($this->container, $this->parameters);
    }

    protected function createServiceTranslator()
    {
        $translator = new Application\Translator\GettextTranslator(
            $this->parameters["rootPath"] . $this->parameters["langDir"] . $this->parameters["lang"] . ".mo",
            new Application\Caching($this->container, $this->parameters),
            $this->parameters["lang"]
        );
        return $translator;
    }

    protected function createServiceApplication()
    {
        return new Application($this->container->session);
    }

    protected function createServiceFilesystem()
    {
        return new Application\FileSystem($this->container, $this->parameters);
    }

    protected function createServiceThumbs()
    {
        return new Application\Thumbs($this->container, $this->parameters);
    }

    protected function createServicePlugins()
    {
        return new Application\Plugins(
            $this->parameters["rootPath"] . $this->parameters["pluginDir"],
            new Application\Caching($this->container, $this->parameters)
        );
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

        $tempDir = $this->container->parameters["tempDir"] . "/file-manager";
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
