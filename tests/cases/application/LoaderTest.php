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
 * Test Loader
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class LoaderTest extends TestCase
{

    /**
     * Test base configuration
     *
     * @return void
     */
    public function testConfiguration()
    {
        $this->createLoader(array("dataDir" => __DIR__));
    }

    /**
     * Test empty dataDir
     *
     * @expectedException \Nette\InvalidArgumentException
     */
    public function testEmptyDataDir()
    {
        $config = array(
            "pluginsDir" => __DIR__,
            "dataDir" => null
        );
        $this->createLoader($config);
    }

    /**
     * Create new loader instance with given configuration
     *
     * @param array $config Configuration
     *
     * @return \Ixtrum\FileManager\Application\Loader
     */
    private function createLoader(array $config)
    {
        return new Ixtrum\FileManager\Application\Loader(new MockSession(), $config);
    }

}