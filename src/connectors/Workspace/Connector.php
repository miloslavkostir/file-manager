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

    /** @var string */
    private $name = "filemanager";

    /**
     * Constructor
     *
     * @param \Nette\DI\Container $container
     */
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
     * Get application name
     *
     * @return string
     */
    public function getAppName()
    {
        return $this->name;
    }

    /**
     * Get application version
     */
    public function getVersion()
    {
        return FileManager::VERSION;
    }

    /**
     * Create application instance
     *
     * @param mixed $configuration Application configuration
     *
     * @return \Ixtrum\FileManager
     */
    public function createInstance($configuration)
    {
        return new FileManager($this->context, $configuration);
    }

}