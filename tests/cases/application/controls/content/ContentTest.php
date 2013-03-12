<?php

class ContentTest extends TestCase
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
        $this->renderComponent($fileManager["control-content"]);
        $this->assertInstanceOf("Nette\Templating\FileTemplate", $fileManager["control-content"]->template);
    }

}