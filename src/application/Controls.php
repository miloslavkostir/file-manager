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

/**
 * Ancestor for all system controls.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
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
    public function __construct(Loader $system, array $selectedFiles = array())
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