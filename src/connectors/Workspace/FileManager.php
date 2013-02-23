<?php

namespace Ixtrum\FileManager\Connectors\Workspace;

use Ixtrum\Workspace\IApplication,
    Ixtrum\FileManager,
    Nette\DI\Container,
    Nette\Object;

class Connector extends Object implements IApplication
{

    /** @var \Nette\DI\Container */
    private $context;

    public function __construct(Container $container)
    {
        $this->context = $container;
    }

    /**
     * Get application title
     */
    public function getTitle()
    {
        return FileManager::NAME;
    }

    /**
     * Get application version
     */
    public function getVersion()
    {
        return FileManager::VERSION;
    }

    public function createInstance($configuration)
    {
        return new FileManager($this->context, $configuration);
    }

}