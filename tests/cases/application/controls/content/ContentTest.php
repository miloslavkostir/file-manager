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
 * Test content control
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
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
        $this->presenter->addComponent($this->createControl(), "testControl");
        $this->presenter->run(new Nette\Application\Request("Homepage", "GET", array()));

        $fileManager = $this->presenter->getComponent("testControl");
        $this->renderComponent($fileManager["control-content"]);
        $this->assertInstanceOf("Nette\Templating\FileTemplate", $fileManager["control-content"]->template);
    }

}