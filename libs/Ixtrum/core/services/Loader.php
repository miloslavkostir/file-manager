<?php

namespace Ixtrum;

use Nette\DI\Container,
        Nette\DirectoryNotFoundException,
        Nette\Application\ApplicationException;


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

                $this->checkRequirements();
        }


        protected function createServiceCaching()
        {
                return new System\Caching($this->container, $this->parameters);
        }


        protected function createServiceTranslator()
        {
                $parameters = $this->parameters;
                $lang = $parameters["rootPath"] . $parameters["langDir"] . $parameters["lang"] . ".mo";

                if (file_exists($lang))
                        return new \GettextTranslator($lang);
                else
                        throw new ApplicationException("Language file $lang does not exists!");
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


        protected function createServicePlugins()
        {
                $pluginDir = $this->parameters["rootPath"] . $this->parameters["pluginDir"];
                return new System\Plugins($pluginDir);
        }


        private function checkRequirements()
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
        }
}
