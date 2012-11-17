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
        $this->initTestDir();

        $presenter = new MockPresenter();
        $presenter->injectPrimary($this->context);
        $this->presenter = $presenter;
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
        $control = new Ixtrum\FileManager($this->context, array("uploadroot" => $this->uploadRoot));
        $this->presenter->addComponent($control, "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));
        $fileManager = $this->presenter->getComponent("testControl");

        $this->assertEquals(array("en" => "en", "cs" => "cs"), $fileManager->getLanguages());
    }

    public function tearDown()
    {
        $this->deInitTestDir();
    }

}