<?php

namespace Ixtrum\System;

use Nette\Utils\Finder,
        Nette\InvalidArgumentException,
        Nette\DI\Container;

class Tools
{
        /** @var Container */
        private $context;

        /** @var array */
        private $config;


        public function __construct(Container $container, Array $config)
        {
                $this->context = $container;
                $this->config = $config;
        }


        /**
        * Get absolute path from relative path
        * @param string $actualdir
        * @return string
        */
        public function getAbsolutePath($actualdir)
        {
                if ($actualdir == $this->getRootname())
                        return $this->config["uploadroot"] . $this->config["uploadpath"];
                else
                        return $this->config["uploadroot"] . substr($this->config["uploadpath"], 0, -1) . $actualdir;
        }


        /**
        * Repair (back)slashes according to OS
        * @param string $path
        * @return string
        */
        public function getRealPath($path)
        {
                $os = strtoupper(substr(PHP_OS, 0, 3));
                if ($os === "WIN")
                    $path = str_replace("/", "\\", $path);
                else
                    $path = str_replace("\\", "/", $path);

                if (realpath($path)) {

                        $path = realpath($path);
                        if (is_dir($path)) {

                                if ($os === "WIN" && substr($path, -1) <> "\\")
                                        $path .= "\\";
                                if ($os <> "WIN" && substr($path, -1) <> "/")
                                        $path .= "/";
                        }

                        return $path;
                } else
                        throw new InvalidArgumentException("Invalid path '$path' given!");
        }

        /**
        * Get root folder name
        * @return string
        */
        public function getRootname()
        {
                $path = $this->config["uploadpath"];
                $first = substr($path, 0, 1);
                $last = substr($path, -1, 1);

                if ( ($first === "/" || $first === "\\") && ($last === "/" || $last === "\\"))
                        $path = substr($path, 1, strlen($path) - 2);
                else
                        throw new InvalidArgumentException("Invalid upload path '$path' given! Correct path starts & ends with \ (Windows) or / (Unix).");

                return $path;
        }


        /**
        * Get used disk space
        * @return integer bytes
        */
        public function getUsedSize()
        {
                $size = 0;
                $files = Finder::findFiles("*")->from($this->config["uploadroot"] . $this->config["uploadpath"]);

                foreach ($files as $file) {

                        $filesClass = new Files($this->context, $this->config);
                        $size += $filesClass->filesize($file->getPathName());
                }

                return $size;
        }


        /**
        * Get details about used disk size
        * @return array
        */
        public function diskSizeInfo()
        {
                $info = array();
                if ($this->config["quota"]) {

                        $size = $this->getUsedSize();
                        $info["usedsize"] = $size;
                        $info["spaceleft"] = ($this->config["quota_limit"] * 1048576) - $size;
                        $info["percentused"] = round(($size / ($this->config["quota_limit"] * 1048576)) * 100);
                } else {

                        $path = $this->config["uploadroot"] . $this->config["uploadpath"];
                        $freesize = disk_free_space($path);
                        $totalsize = disk_total_space($path);
                        $info["usedsize"] = $totalsize - $freesize;
                        $info["spaceleft"] = $freesize;
                        $info["percentused"] = round(($info["usedsize"] / $totalsize ) * 100);
                }

                return $info;
        }


        /**
        * Check if realtive path is valid
         * TODO - move it
        * @param string $dir
        * @param string $file (optional)
        * @return bool
        */
        public function validPath($dir, $file = NULL)
        {
                $path = $this->getAbsolutePath($dir);

                if ($file)
                        $path .= $file;

                if (file_exists($path))
                        return true;
                else
                        return false;
        }
}