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
 * Test Configurator
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class ConfiguratorTest extends TestCase
{

    /** @var \Ixtrum\FileManager\Configurator */
    private $instance;

    /** @var array */
    private $defaults = array(
        "appDir" => null,
        "dataDir" => null,
        "cache" => true,
        "cacheDir" => null,
        "cacheStorage" => "FileStorage",
        "thumbs" => true,
        "thumbsDir" => null,
        "resUrl" => "ixtrum-res",
        "readonly" => false,
        "quota" => false,
        "quotaLimit" => 20, // megabytes
        "lang" => "en",
        "langs" => array(),
        "langDir" => null,
        "plugins" => array(),
        "pluginsDir" => null
    );

    /**
     * Set up test
     */
    public function setUp()
    {
        parent::setUp();
        $this->instance = new Ixtrum\FileManager\Configurator;
    }

    /**
     * Test empty configuration
     *
     * @expectedException \Exception
     *
     * @return void
     */
    public function testCreateConfig()
    {
        $defaults = $this->createDefaults();
        $expected = array(
            "dataDir" => realpath($this->dataDir),
            "appDir" => $defaults["appDir"],
            "cache" => $defaults["cache"],
            "cacheDir" => $defaults["cacheDir"],
            "cacheStorage" => $defaults["cacheStorage"],
            "thumbs" => $defaults["thumbs"],
            "thumbsDir" => $defaults["thumbsDir"],
            "resUrl" => $defaults["resUrl"],
            "readonly" => $defaults["readonly"],
            "quota" => $defaults["quota"],
            "quotaLimit" => $defaults["quotaLimit"],
            "lang" => $defaults["lang"],
            "langs" => $defaults["langs"],
            "langDir" => $defaults["langDir"],
            "plugins" => $defaults["plugins"],
            "pluginsDir" => $defaults["pluginsDir"],
            "resDir" => $defaults["resDir"]
        );
        $this->assertEquals(
                $expected, $this->instance->createConfig(array("dataDir" => $this->dataDir))
        );
        $this->instance->createConfig(array());
    }

    /**
     * Create default configuration
     *
     * @return array
     */
    private function createDefaults()
    {
        $reflection = new \ReflectionClass("\Ixtrum\FileManager");
        $appDir = dirname($reflection->getFileName());

        // Get and complete default system parameters
        $defaults = $this->defaults;
        $defaults["pluginsDir"] = "$appDir/plugins";
        $defaults["langDir"] = "$appDir/lang";

        return $defaults;
    }

}