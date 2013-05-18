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

use Ixtrum\FileManager\Loader;

/**
 * Ancestor for all plugins.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
abstract class Plugins extends \Ixtrum\FileManager
{

    /** @var \Ixtrum\FileManager\Loader */
    protected $system;

    /** @var string */
    protected $view;

    /** @var array */
    protected $selectedFiles;

    /** @var array */
    protected $config;

    /** @var string */
    protected $resUrl;

    /** @var \Ixtrum\FileManager\Translator */
    protected $translator;

    /** @var string $name Plugin name */
    protected $name;

    /**
     * Constructor
     *
     * @param string                     $name          Plugin name
     * @param \Ixtrum\FileManager\Loader $system        Application container
     * @param array                      $selectedFiles Selected files from POST request
     * @param string                     $view          Content view
     */
    public function __construct($name, Loader $system, array $selectedFiles = array(), $view)
    {
        $this->name = $name;
        $this->system = $system;
        $this->selectedFiles = $selectedFiles;
        $this->config = $this->system->parameters["plugins"][$this->name];
        $this->resUrl = $this->system->parameters["resUrl"] . "/plugins/$this->name";
        $this->view = $view;

        $languageDir = self::getPluginDir() . "/lang";
        if (is_dir($languageDir)) {
            $this->translator = new Translator;
            $this->translator->init($languageDir, $this->system->parameters["lang"]);
        }
    }

    /**
     * Get path to child plugin class
     *
     * @return string
     */
    private static function getPluginDir()
    {
        $reflection = new \Nette\Reflection\ClassType(get_called_class());
        return dirname($reflection->getFilename());
    }

}