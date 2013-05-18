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
     * Test caching service
     */
    public function testCreateServiceCaching()
    {
        $loader = $this->createLoader(array("dataDir" => $this->dataDir, "cache" => false));
        $this->assertInstanceOf("stdClass", $loader->caching);

        $loader = $this->createLoader(array("dataDir" => $this->dataDir));
        $this->assertInstanceOf("Ixtrum\FileManager\Caching", $loader->caching);
    }

    /**
     * Test translator service
     */
    public function testCreateServiceTranslator()
    {
        $loader = $this->createLoader(array("dataDir" => $this->dataDir));
        $this->assertInstanceOf("Ixtrum\FileManager\Translator", $loader->translator);
    }

    /**
     * Test session service
     */
    public function testCreateServiceSession()
    {
        $loader = $this->createLoader(array("dataDir" => $this->dataDir));
        $this->assertInstanceOf("Nette\Http\SessionSection", $loader->session);
    }

    /**
     * Test filesystem service
     */
    public function testCreateServiceFilesystem()
    {
        $loader = $this->createLoader(array("dataDir" => $this->dataDir));
        $this->assertInstanceOf("Ixtrum\FileManager\FileSystem", $loader->filesystem);
    }

    /**
     * Test thumbs service
     */
    public function testCreateServiceThumbs()
    {
        $loader = $this->createLoader(array("dataDir" => $this->dataDir, "thumbs" => false));
        $this->assertInstanceOf("stdClass", $loader->thumbs);

        $loader = $this->createLoader(array("dataDir" => $this->dataDir, "thumbsDir" => $this->context->parameters["tempDir"] . "/thumbs"));
        $this->assertInstanceOf("Ixtrum\FileManager\Thumbs", $loader->thumbs);
    }

    /**
     * Create new loader instance with given configuration
     *
     * @param array $config Configuration
     *
     * @return \Ixtrum\FileManager\Loader
     */
    private function createLoader(array $config)
    {
        return new Ixtrum\FileManager\Loader(new MockSession, $this->context->cacheStorage, $config);
    }

}