<?php

namespace Ixtrum\FileManager\Services;

use Nette\DI\Container,
        Nette\DirectoryNotFoundException,
        Nette\Application\ApplicationException,
        Ixtrum\FileManager\System;


final class Loader extends Container
{
        /** @var Container */
        private $container;


        public function __construct(Container $container, $userConfig, $rootPath)
        {
                $loader = new \Nette\Config\Loader;
                $config = $loader->load("$rootPath/config/config.neon");
                $config["parameters"] = array_merge($config["parameters"], $userConfig);
                $config["parameters"]["rootPath"] =  $rootPath;

                if (!isset($config["parameters"]["uploadroot"]))
                        $config["parameters"]["uploadroot"] = $rootPath;

                $this->parameters = $config["parameters"];
                $this->container = $container;

                $this->init();
        }


        protected function createServiceCaching()
        {
                return new System\Caching($this->container, $this->parameters);
        }


        protected function createServiceTranslator()
        {
                $param = $this->parameters;
                $langFile = $param["rootPath"] . $param["langDir"] . $param["lang"] . ".mo";

                return new System\Translator\GettextTranslator($langFile);
        }


        protected function createServiceSystem()
        {
                return new System($this->container->session);
        }


        protected function createServiceTools()
        {
                return new System\Tools($this->container, $this->parameters);
        }


        protected function createServiceFiles()
        {
                return new System\Files($this->container, $this->parameters);
        }


        protected function createServiceThumbs()
        {
                return new System\Thumbs($this->container, $this->parameters);
        }


        protected function createServicePlugins()
        {
                $pluginDir = $this->parameters["rootPath"] . $this->parameters["pluginDir"];
                return new System\Plugins($pluginDir, new System\Caching($this->container, $this->parameters));
        }


        private function init()
        {
                $config = $this->parameters;
                $uploadPath = $config["uploadroot"] . $config["uploadpath"];
                $resDir = $this->container->parameters["wwwDir"] . $config["resource_dir"];

                if (!is_dir($resDir))
                        throw new DirectoryNotFoundException("Resource dir '$resDir' does not exist!");

                if (!is_dir($uploadPath))
                         throw new DirectoryNotFoundException("Upload path '$uploadPath' doesn't exist!");

                if (!is_writable($uploadPath))
                         throw new ApplicationException("Upload path '$uploadPath' must be writable!");

                if (!is_dir($uploadPath))
                         throw new DirectoryNotFoundException("Resource path '$uploadPath' doesn't exist!");

                $tempDir = $this->container->parameters["tempDir"] . "/file-manager";
                if (!is_dir($tempDir)) {

                        $oldumask = umask(0);
                        mkdir($tempDir, 0777);
                        umask($oldumask);
                }

                if (!is_writable($tempDir))
                         throw new ApplicationException("Temp dir '$tempDir' must be writable!");
        }
}
