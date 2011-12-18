<?php

use Nette\Config\Loader,
        Nette\Caching\Cache;

class ConfigurationModel extends BaseModel
{
        public function load()
        {
                $loader = new Loader;
                $config = $loader->load($this->context->parameters["configDir"] . "custom.neon");
                return $this->filter($config);
        }

        function filter($config)
        {
                $config = $config["parameters"]["security"];
                return $config;
        }

        public function save($values)
        {
                $loader = new Loader;
                $config = $loader->load($this->context->parameters["configDir"] . "custom.neon");

                $config["parameters"]["security"]["salt"] = $values;
                $loader->save($config, $this->context->parameters["configDir"] . "custom.neon");
                $this->context->cacheStorage->clean(array(Cache::ALL => TRUE));
        }
}