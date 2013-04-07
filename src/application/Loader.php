<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Application;

use Nette\DI\Container;

/**
 * System container with all services.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
final class Loader extends Container
{

    /** @var \Nette\Http\Session */
    private $session;

    /**
     * Constructor
     *
     * @param \Nette\Http\Session $session Session
     * @param array               $config  Custom configuration
     */
    public function __construct(\Nette\Http\Session $session, $config)
    {
        $this->session = $session;
        $configurator = new Configurator;
        $this->parameters = $configurator->createConfig($config);
    }

    /**
     * Create service caching
     *
     * @return \Ixtrum\FileManager\Application\Caching | \stdClass
     */
    protected function createServiceCaching()
    {
        if (!$this->parameters["cache"]) {
            return new \stdClass;
        }
        return new Caching($this->parameters["cacheStorage"], $this->parameters["cacheDir"]);
    }

    /**
     * Create service translator
     *
     * @return \Ixtrum\FileManager\Application\Translator
     */
    protected function createServiceTranslator()
    {
        $translator = new Translator;
        return $translator->init($this->parameters["langDir"] . DIRECTORY_SEPARATOR . $this->parameters["lang"] . ".json");
    }

    /**
     * Create service session
     *
     * @return \Ixtrum\FileManager\Application\Session
     */
    protected function createServiceSession()
    {
        return new Session($this->session->getSection("file-manager"));
    }

    /**
     * Create service fileSystem
     *
     * @return \Ixtrum\FileManager\Application\FileSystem
     */
    protected function createServiceFilesystem()
    {
        return new FileSystem;
    }

    /**
     * Create service thumbs
     *
     * @return \Ixtrum\FileManager\Application\Thumbs | \stdClass
     */
    protected function createServiceThumbs()
    {
        if (!$this->parameters["thumbs"]) {
            return new \stdClass;
        }
        return new Thumbs($this->parameters["thumbsDir"]);
    }

}