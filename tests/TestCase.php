<?php

class TestCase extends PHPUnit_Framework_TestCase
{

    /**  @var Nette\DI\Container */
    public $context;

    /** @var string */
    public $uploadRoot;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // Create tempDir
        $tempDir = __DIR__ . "/temp";
        if (!is_dir($tempDir)) {
            $this->mkdir($tempDir);
        }

        // Set up configuration
        $configurator = new Nette\Config\Configurator;
        $configurator->setTempDirectory($tempDir);
        $container = $configurator->createContainer();

        // Create wwwDir
        $wwwDir = "$tempDir/www";
        if (!is_dir($wwwDir)) {
            $this->mkdir($wwwDir);
        }
        $container->parameters["wwwDir"] = $wwwDir;

        // Set up router
        $container->router = new Nette\Application\Routers\SimpleRouter();

        $this->context = $container;

        // Set up upload root dirs
        $this->uploadRoot = $container->parameters["tempDir"] . "/uploadroot";
    }

    /**
     * Render method helper for testing components and controls
     *
     * @param Nette\Application\UI\Control $control Component
     * @param array                        $args    Arguments
     *
     * @return string
     */
    public function renderComponent(Nette\Application\UI\Control $control, $args = array())
    {
        ob_start();
        callback($control, "render")->invokeArgs($args);
        return ob_get_clean();
    }

    /**
     * Create writeable dir
     *
     * @param string $path Path to dir
     */
    public function mkdir($path)
    {
        if (!is_dir($path)) {
            $oldumask = umask(0);
            mkdir($path, 0777);
            umask($oldumask);
        }
    }

    /**
     * Remove directory recursively
     *
     * @param string  $directory Path to directory
     * @param boolean $empty     Only empty directory
     *
     * @return boolean
     */
    public function rmdir($directory, $empty = false)
    {
        if (substr($directory, -1) == "/") {
            $directory = substr($directory, 0, -1);
        }
        if (!file_exists($directory) || !is_dir($directory)) {
            return false;
        } elseif (is_readable($directory)) {
            $handle = opendir($directory);
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    $path = $directory . "/" . $item;
                    if (is_dir($path)) {
                        $this->rmdir($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            closedir($handle);
            if ($empty == false) {
                if (!rmdir($directory)) {
                    return false;
                }
            }
        }
        return true;
    }

    public function setUp()
    {
        $this->mkdir($this->uploadRoot);
    }
    
    public function tearDown()
    {
        $this->rmdir($this->uploadRoot);
    }
}