<?php

class TestCase extends PHPUnit_Framework_TestCase
{

    /**  @var Nette\DI\Container */
    public $context;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->context = new Nette\DI\Container();
        $this->context->router = new Nette\Application\Routers\SimpleRouter();
    }

    /**
     * Render method helper for testing components and controls
     *
     * @param Nette\Application\UI\Control $control Component
     * @param array                        $args    Arguments
     *
     * @return string
     */
    public function renderComponent(Nette\Application\UI\Control $control, $args = array())
    {
        ob_start();
        callback($control, "render")->invokeArgs($args);
        return ob_get_clean();
    }

}