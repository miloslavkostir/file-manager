<?php

class TestCase extends PHPUnit_Framework_TestCase
{

    /**  @var Nette\DI\Container */
    public $context;

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
        $oldumask = umask(0);
        mkdir($path, 0777);
        umask($oldumask);
    }

}