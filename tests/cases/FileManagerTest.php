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

    }

    /**
     * Test default render method
     *
     * @return void
     */
    public function testRender()
    {
        $presenter = new MockPresenter();
        $presenter->injectPrimary($this->context);
        $control = new Ixtrum\FileManager($this->context, array("uploadroot" => $this->uploadRoot, "uploadpath" => $this->uploadPath));
        $presenter->addComponent($control, "testControl");
        $presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $presenter->getComponent("testControl");
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
        $this->initTestDir();

        $presenter = new MockPresenter();
        $presenter->injectPrimary($this->context);

        $this->mkdir($this->uploadRoot . $this->uploadPath . "testing");
        $control = new Ixtrum\FileManager($this->context, array("uploadroot" => $this->uploadRoot, "uploadpath" => $this->uploadPath));
        $presenter->addComponent($control, "testControl");
        $presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $presenter->getComponent("testControl");
        $fileManager->setActualDir("/testing/");

        $this->assertEquals("/testing/", $fileManager->getActualDir());

        $this->deInitTestDir();
    }

}