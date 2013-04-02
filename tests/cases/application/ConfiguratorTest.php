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

    /** @var \Ixtrum\FileManager\Application\Configurator */
    private $instance;

    /**
     * Set up test
     */
    public function setUp()
    {
        parent::setUp();
        $this->instance = new Ixtrum\FileManager\Application\Configurator;
    }

    /**
     * Test empty configuration
     *
     * @expectedException \Nette\InvalidArgumentException
     *
     * @return void
     */
    public function testCreateConfig()
    {
        $this->instance->createConfig(array("dataDir" => $this->dataDir));
        $this->instance->createConfig(array());
    }
    
}