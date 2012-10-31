<?php

class FileManagerTest extends TestCase
{

    /** @var Nette\Application\UI\Control */
    private $control;

    /**
     * Set up test
     *
     * @return void
     */
    public function setUp()
    {
        $this->control = new Ixtrum\FileManager($this->context);
    }

    /**
     * Test default render
     *
     * @return void
     */
    public function testRender()
    {
        $presenter = new MockPresenter();
        $presenter->injectPrimary($this->context);
        $presenter->addComponent($this->control, "testControl");
        $presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $control = $presenter["testControl"];
        $this->renderComponent($control);
        $this->assertInstanceOf("Nette\Templating\FileTemplate", $control->template);
    }

}