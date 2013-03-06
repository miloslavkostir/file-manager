<?php

class FileManagerTest extends TestCase
{

    /** @var Nette\Application\UI\Presenter */
    private $presenter;

    /**
     * Set up test
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $presenter = new MockPresenter();
        $presenter->injectPrimary($this->context);
        $this->presenter = $presenter;
    }

    public function testIsPathValid()
    {
        $control = new Ixtrum\FileManager($this->context, array("uploadroot" => $this->uploadRoot));
        $this->presenter->addComponent($control, "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));
        $fileManager = $this->presenter->getComponent("testControl");

        $dir = "/test/";
        $this->mkdir($this->uploadRoot . $dir);
        $this->assertTrue($fileManager->isPathValid($dir));

        // Non-existing files are not valid
        $this->assertFalse($fileManager->isPathValid("/test/", "non-existing-file"));

        // Folders in uploadroot superior are not valid
        $this->assertFalse($fileManager->isPathValid("/../"));

        // Uploadroot is valid
        $this->assertTrue($fileManager->isPathValid("/"));

        // Non-existing folders are not valid
        $this->assertFalse($fileManager->isPathValid("/missing/"));

        // Files in uploadroot subfolder are valid
        $dir = "/test/";
        $file = "test.txt";
        file_put_contents($this->uploadRoot . $dir . $file, "data");
        $this->assertTrue($fileManager->isPathValid($dir, $file));

        // Files in uploadroot are valid
        $dir = "/";
        $file = "test.txt";
        file_put_contents($this->uploadRoot . $dir . $file, "data");
        $this->assertTrue($fileManager->isPathValid($dir, $file));

        // Files in uploadroot superior are not valid
        $dir = "/../";
        $file = "test.txt";
        file_put_contents($this->uploadRoot . $dir . $file, "data");
        $this->assertFalse($fileManager->isPathValid($dir, $file));
        unlink($this->uploadRoot . $dir . $file);
    }

    /**
     * Test default render method
     *
     * @return void
     */
    public function testRender()
    {
        $control = new Ixtrum\FileManager($this->context, array("uploadroot" => $this->uploadRoot));
        $this->presenter->addComponent($control, "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $this->renderComponent($fileManager);
        $this->assertInstanceOf("Nette\Templating\FileTemplate", $fileManager->template);
    }

    /**
     * Test method setActualDir
     *
     * @return void
     */
    public function testSetActualDir()
    {
        $this->mkdir($this->uploadRoot . "/testing");
        $control = new Ixtrum\FileManager($this->context, array("uploadroot" => $this->uploadRoot));
        $this->presenter->addComponent($control, "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $fileManager->setActualDir("/testing/");

        $this->assertEquals("/testing/", $fileManager->getActualDir());
    }

    /**
     * Test method getLanguages
     *
     * @return void
     */
    public function testGetLanguages()
    {
        $this->assertEquals(
            array("en" => "en", "cs" => "cs"), Ixtrum\FileManager::getLanguages(__DIR__ . "/../../src/lang")
        );
    }

}