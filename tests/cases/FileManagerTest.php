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
        $context = new Nette\DI\Container();
        $context = $this->context;

        $application = new MockApplication();
        $application = $this->context->application;

        $httpContext = new MockHttpContext();
        $httpContext = new Nette\Http\Context($this->context->httpRequest, $this->context->httpResponse);

        $httpRequest = new MockHttpRequest();
        $httpRequest = $this->context->httpRequest;

        $httpResponse = new Nette\Http\Response();
        $httpResponse = $this->context->httpResponse;

        $session = new MockSession();
        $user = new MockUser();
        $applicationRequest = new Nette\Application\Request('Mock', '', array());

        $presenter = new MockPresenter();
        //$presenter->autoCanonicalize = false;
        $presenter->injectPrimary($context, $application, $httpContext, $httpRequest, $httpResponse, $session, $user);
        $presenter->addComponent($this->control, "testControl");
        //$presenter->run($applicationRequest);
        $this->assertTrue(false);
        dump($this->renderComponent($presenter["testControl"]));
    }

}