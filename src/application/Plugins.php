<?php

namespace Ixtrum\FileManager\Application;

use Ixtrum\FileManager\Application\Loader;

abstract class Plugins extends \Ixtrum\FileManager
{

    /** @var \Ixtrum\FileManager\Application\Loader */
    protected $system;

    /** @var string */
    protected $view;

    /** @var array */
    protected $selectedFiles;

    /** @var string */
    protected $resDir;

    /** @var array */
    protected $config;

    /**
     * Constructor
     *
     * @param string $name Plugin name
     * @param \Ixtrum\FileManager\Application\Loader $system        Application container
     * @param array                                  $selectedFiles Selected files from POST request
     */
    public function __construct($name, Loader $system, array $selectedFiles = array())
    {
        $this->system = $system;
        $this->selectedFiles = $selectedFiles;
        $this->config = $this->system->parameters["plugins"][$name];
        $this->resDir = $this->system->parameters["resDir"] . "/plugins/$name";

        // Get & validate selected view
        $view = $system->session->get("view");
        $allowedViews = array("details", "large", "list", "small");
        if (!empty($view) && in_array($view, $allowedViews)) {
            $this->view = $view;
        } else {
            $this->view = "large";
        }
    }

}