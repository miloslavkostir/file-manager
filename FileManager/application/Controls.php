<?php

namespace Ixtrum\FileManager\Application;

abstract class Controls extends \Ixtrum\FileManager
{

    /** @var \Ixtrum\FileManager\Application\Loader */
    protected $system;

    /** @var string */
    protected $view;

    /** @var array */
    protected $selectedFiles;

    /**
     * Constructor
     *
     * @param \Ixtrum\FileManager\Application\Loader $system        Application container
     * @param array                                  $selectedFiles Selected files from POST request
     */
    public function __construct(\Ixtrum\FileManager\Application\Loader $system, array $selectedFiles = array())
    {
        $this->system = $system;
        $this->selectedFiles = $selectedFiles;

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