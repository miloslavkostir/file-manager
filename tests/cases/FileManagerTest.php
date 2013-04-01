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
 * Test FileManager class
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class FileManagerTest extends TestCase
{

    /** @var Nette\Application\UI\Presenter */
    private $presenter;

    /**
     * Set up test
     */
    public function setUp()
    {
        parent::setUp();
        $presenter = new MockPresenter();
        $presenter->injectPrimary($this->context);
        $this->presenter = $presenter;
    }

    /**
     * Test isPathValid method
     */
    public function testIsPathValid()
    {
        $this->presenter->addComponent($this->createControl(), "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));
        $fileManager = $this->presenter->getComponent("testControl");

        $dir = "/test/";
        $this->mkdir($this->dataDir . $dir);
        $this->assertTrue($fileManager->isPathValid($dir));

        // Non-existing files are not valid
        $this->assertFalse($fileManager->isPathValid("/test/", "non-existing-file"));

        // Folders in dataDir superior are not valid
        $this->assertFalse($fileManager->isPathValid("/../"));

        // dataDir is valid
        $this->assertTrue($fileManager->isPathValid("/"));

        // Non-existing folders are not valid
        $this->assertFalse($fileManager->isPathValid("/missing/"));

        // Files in dataDir subfolder are valid
        $dir = "/test/";
        $file = "test.txt";
        file_put_contents($this->dataDir . $dir . $file, "data");
        $this->assertTrue($fileManager->isPathValid($dir, $file));

        // Files in dataDir are valid
        $dir = "/";
        $file = "test.txt";
        file_put_contents($this->dataDir . $dir . $file, "data");
        $this->assertTrue($fileManager->isPathValid($dir, $file));

        // Files in dataDir superior are not valid
        $dir = "/../";
        $file = "test.txt";
        file_put_contents($this->dataDir . $dir . $file, "data");
        $this->assertFalse($fileManager->isPathValid($dir, $file));
        unlink($this->dataDir . $dir . $file);
    }

    /**
     * Test render
     */
    public function testRender()
    {
        $this->presenter->addComponent($this->createControl(), "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $this->renderComponent($fileManager);
    }

    /**
     * Test render css.latte
     */
    public function testRenderCss()
    {
        $this->presenter->addComponent($this->createControl(), "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $this->renderComponent($fileManager, "css");

        $this->assertInstanceOf("Nette\Templating\FileTemplate", $fileManager->template);
        $this->assertEquals("css.latte", basename($fileManager->template->getFile()));
    }

    /**
     * Test render content.latte
     */
    public function testRenderContent()
    {
        $this->presenter->addComponent($this->createControl(), "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $this->renderComponent($fileManager, "content");

        $this->assertInstanceOf("Nette\Templating\FileTemplate", $fileManager->template);
        $this->assertEquals("content.latte", basename($fileManager->template->getFile()));
    }

    /**
     * Test render toolbar.latte
     */
    public function testRenderToolbar()
    {
        $this->presenter->addComponent($this->createControl(), "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $this->renderComponent($fileManager, "toolbar");

        $this->assertInstanceOf("Nette\Templating\FileTemplate", $fileManager->template);
        $this->assertEquals("toolbar.latte", basename($fileManager->template->getFile()));
    }

    /**
     * Test render addressbar.latte
     */
    public function testRenderAddressbar()
    {
        $this->presenter->addComponent($this->createControl(), "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $this->renderComponent($fileManager, "addressbar");

        $this->assertInstanceOf("Nette\Templating\FileTemplate", $fileManager->template);
        $this->assertEquals("addressbar.latte", basename($fileManager->template->getFile()));
    }

    /**
     * Test render infobar.latte
     */
    public function testRenderInfobar()
    {
        $this->presenter->addComponent($this->createControl(), "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $this->renderComponent($fileManager, "infobar");

        $this->assertInstanceOf("Nette\Templating\FileTemplate", $fileManager->template);
        $this->assertEquals("infobar.latte", basename($fileManager->template->getFile()));
    }

    /**
     * Test render messages.latte
     */
    public function testRenderMessages()
    {
        $this->presenter->addComponent($this->createControl(), "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $this->renderComponent($fileManager, "messages");

        $this->assertInstanceOf("Nette\Templating\FileTemplate", $fileManager->template);
        $this->assertEquals("messages.latte", basename($fileManager->template->getFile()));
    }

    /**
     * Test render scripts.latte
     */
    public function testRenderScripts()
    {
        $this->presenter->addComponent($this->createControl(), "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $this->renderComponent($fileManager, "scripts");

        $this->assertInstanceOf("Nette\Templating\FileTemplate", $fileManager->template);
        $this->assertEquals("scripts.latte", basename($fileManager->template->getFile()));
    }

    /**
     * Test method setActualDir
     */
    public function testSetActualDir()
    {
        $this->mkdir($this->dataDir . "/testing");
        $this->presenter->addComponent($this->createControl(), "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $fileManager->setActualDir("/testing/");

        $this->assertEquals("/testing/", $fileManager->getActualDir());
    }

    /**
     * Test method getLanguages
     */
    public function testGetLanguages()
    {
        $this->assertEquals(
                array("en" => "en", "cs" => "cs"), Ixtrum\FileManager::getLanguages(__DIR__ . "/../../src/lang")
        );
    }

}