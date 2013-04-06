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

use Ixtrum\FileManager\Application\Loader;

/**
 * Ancestor for all plugins.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
abstract class Plugins extends \Ixtrum\FileManager
{

    /** @var \Ixtrum\FileManager\Application\Loader */
    protected $system;

    /** @var string */
    protected $view;

    /** @var array */
    protected $selected;

    /** @var array */
    protected $config;

    /** @var string */
    protected $resUrl;

    /**
     * Constructor
     *
     * @param string                                 $name     Plugin name
     * @param \Ixtrum\FileManager\Application\Loader $system   Application container
     * @param array                                  $selected Selected files/dirs
     */
    public function __construct($name, Loader $system, array $selected)
    {
        $this->system = $system;
        $this->selected = $selected;
        $this->config = $this->system->parameters["plugins"][$name];
        $this->resUrl = $this->system->parameters["resUrl"] . "/plugins/$name";

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