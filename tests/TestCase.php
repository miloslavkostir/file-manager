<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

/**
 * Main test case.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class TestCase extends PHPUnit_Framework_TestCase
{

    /**  @var \Nette\DI\Container */
    public $context;

    /** @var string */
    public $dataDir;

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

        // Get system container
        if (class_exists("SystemContainer")) {
            $container = new SystemContainer;
        } else {
            $container = $configurator->createContainer();
        }

        // Create wwwDir
        $wwwDir = "$tempDir/www";
        if (!is_dir($wwwDir)) {
            $this->mkdir($wwwDir);
        }
        $container->parameters["wwwDir"] = $wwwDir;

        // Set up router
        $container->router = new Nette\Application\Routers\SimpleRouter();

        $this->context = $container;

        // Set up dataDir
        $this->dataDir = $container->parameters["tempDir"] . "/dataDir";
    }

    /**
     * Create test control
     *
     * @param array $config Custom configuration
     *
     * @return \Ixtrum\FileManager
     */
    protected function createControl($config = array())
    {
        $default = array("dataDir" => $this->dataDir);
        $control = new Ixtrum\FileManager(
            $this->context->httpRequest, $this->context->session, $this->context->cacheStorage
        );
        $control->init(array_merge($default, $config));
        return $control;
    }

    /**
     * Render method helper for testing components and controls
     *
     * @param Nette\Application\UI\Control $control Component
     * @param string                       $method  Set different render method
     * @param array                        $args    Arguments
     *
     * @return string
     */
    public function renderComponent(Nette\Application\UI\Control $control, $method = null, $args = array())
    {
        $method = ucfirst($method);
        ob_start();
        callback($control, "render$method")->invokeArgs($args);
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

    /**
     * Set up test
     */
    public function setUp()
    {
        if (is_dir($this->dataDir)) {
            $this->rmdir($this->dataDir);
        }
        $this->mkdir($this->dataDir);
    }

    /**
     * Tear down test
     */
    public function tearDown()
    {
        $this->rmdir($this->dataDir);
    }

}