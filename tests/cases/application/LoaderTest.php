<?php

class LoaderTest extends TestCase
{

    /**
     * Test base configuration
     *
     * @return void
     */
    public function testConfiguration()
    {
        $config = array(
            "appDir" => __DIR__,
            "pluginDir" => __DIR__,
            "uploadroot" => __DIR__,
            "langDir" => __DIR__
        );
        $this->createLoader($config);
    }

    /**
     * Test empty uploadroot
     *
     * @expectedException \Nette\InvalidArgumentException
     */
    public function testEmptyUploadroot()
    {
        $config = array(
            "appDir" => __DIR__,
            "pluginDir" => __DIR__,
            "uploadroot" => null
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