<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager;

/**
 * System container with all services.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
final class Loader extends \Nette\DI\Container
{

    /** @var \Nette\Http\Session */
    private $session;

    /** @var \Nette\Caching\IStorage */
    private $cacheStorage;

    /**
     * Constructor
     *
     * @param \Nette\Http\Session     $session      Session
     * @param \Nette\Caching\IStorage $cacheStorage Cache storage
     * @param array                   $config       Custom configuration
     */
    public function __construct(\Nette\Http\Session $session, \Nette\Caching\IStorage $cacheStorage, $config)
    {
        $this->session = $session;
        $this->cacheStorage = $cacheStorage;
        $configurator = new Configurator;
        $this->parameters = $configurator->createConfig($config);
    }

    /**
     * Create service caching
     *
     * @return \Ixtrum\FileManager\Caching | \stdClass
     */
    protected function createServiceCaching()
    {
        if (!$this->parameters["cache"]) {
            return new \stdClass;
        }
        return new Caching($this->cacheStorage);
    }

    /**
     * Create service translator
     *
     * @return \Ixtrum\FileManager\Translator
     */
    protected function createServiceTranslator()
    {
        $translator = new Translator;
        return $translator->init($this->parameters["langDir"], $this->parameters["lang"]);
    }

    /**
     * Create service session
     *
     * @return \Nette\Http\SessionSection
     */
    protected function createServiceSession()
    {
        return $this->session->getSection("ixtrum-file-manager");
    }

    /**
     * Create service fileSystem
     *
     * @return \Ixtrum\FileManager\FileSystem
     */
    protected function createServiceFilesystem()
    {
        return new FileSystem;
    }

    /**
     * Create service thumbs
     *
     * @return \Ixtrum\FileManager\Thumbs | \stdClass
     */
    protected function createServiceThumbs()
    {
        if (!$this->parameters["thumbs"]) {
            return new \stdClass;
        }
        return new Thumbs($this->parameters["thumbsDir"]);
    }

}