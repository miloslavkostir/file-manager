<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum;

use Ixtrum\FileManager\Application\FileSystem,
    Nette\Http\Session,
    Nette\Http\Request,
    Nette\Application\UI\Form,
    Nette\Application\UI\Control,
    Nette\Application\UI\Multiplier;

/**
 * File Manager base class.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class FileManager extends Control
{

    const NAME = "iXtrum File Manager";
    const VERSION = "1.0-beta4";

    /** @var \Ixtrum\FileManager\Application\Loader */
    protected $system;

    /** @var array */
    protected $selectedFiles = array();

    /**
     * Constructor
     *
     * @param \Nette\Http\Request $request HTTP request
     * @param \Nette\Http\Session $session Session
     * @param array               $config  Custom configuration
     */
    public function __construct(Request $request, Session $session, $config = array())
    {
        parent::__construct();

        // Create system container with services and configuration
        $this->system = new FileManager\Application\Loader($session, $config);
        $this->system->freeze();

        // Get & validate actual dir
        $actualDir = $this->system->session->get("actualdir");
        $actualPath = $this->getAbsolutePath($actualDir);
        if (!is_dir($actualPath) || empty($actualDir)) {
            // Set root dir as default
            $actualDir = FileSystem::getRootname();
        }
        $this->setActualDir($actualDir);

        // Get selected files via POST
        $selectedFiles = $request->getPost("files");
        if (is_array($selectedFiles)) {
            $this->selectedFiles = $selectedFiles;
        }

        $this->invalidateControl();
    }

    /**
     * Show new dir control
     */
    public function handleRunNewFolder()
    {
        $this->template->run = "newfolder";
    }

    /**
     * Show rename control
     */
    public function handleRunRename()
    {
        $this->template->run = "rename";
    }

    /**
     * Refresh content - clear cache
     */
    public function handleRefreshContent()
    {
        if ($this->system->parameters["cache"]) {

            $this->system->caching->deleteItem(null, array("tags" => "treeview"));
            $this->system->caching->deleteItem(array(
                "content",
                $this->getAbsolutePath($this->getActualDir())
            ));
        }
    }

    /**
     * Run plugin
     *
     * @param string $name Plugin name
     */
    public function handleRunPlugin($name)
    {
        // Find valid plugin
        foreach ($this->system->parameters["plugins"] as $plugin) {

            if ($name === $plugin["name"]) {
                $this->template->plugin = $name;
            }
        }

        if (!isset($this->template->plugin)) {
            $this->flashMessage($this->system->translator->translate("Plugin '%s' not found!", $name), "warning");
        }
    }

    /**
     * Getter for actualDir
     *
     * @return string Actual directory
     */
    public function getActualDir()
    {
        return $this->system->session->get("actualdir");
    }

    /**
     * Set actual dir
     *
     * @param string $dir relative dir path
     */
    public function setActualDir($dir)
    {
        if ($this->isPathValid($dir)) {
            $this->system->session->set("actualdir", $dir);
        }
    }

    /**
     * Render all
     */
    public function render()
    {
        $this->renderCss();
        $this->renderAddressbar();
        $this->renderToolbar();
        $this->renderMessages();
        $this->renderContent();
        $this->renderInfobar();
        $this->renderScripts();
    }

    /**
     * Render addressbar
     */
    public function renderAddressbar()
    {
        $this->template->setFile(__DIR__ . "/templates/addressbar.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->render();
    }

    /**
     * Render content
     */
    public function renderContent()
    {
        $this->template->setFile(__DIR__ . "/templates/content.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->render();
    }

    /**
     * Render css
     */
    public function renderCss()
    {
        $this->template->setFile(__DIR__ . "/templates/css.latte");
        $this->template->resUrl = $this->system->parameters["resUrl"];

        // Get theme
        $theme = $this->system->session->get("theme");
        if ($theme) {
            $this->template->theme = $theme;
        } else {
            $this->template->theme = "default";
        }
        $this->template->render();
    }

    /**
     * Render infobar
     */
    public function renderInfobar()
    {
        $this->template->setFile(__DIR__ . "/templates/infobar.latte");
        $this->template->setTranslator($this->system->translator);

        // Get plugins
        $this->template->fileInfoPlugins = array();
        foreach ($this->system->parameters["plugins"] as $plugin) {

            if (in_array("fileinfo", $plugin["integration"])) {
                $this->template->fileInfoPlugins[] = $plugin;
            }
        }
        $this->template->render();
    }

    /**
     * Render messages
     */
    public function renderMessages()
    {
        $this->template->setFile(__DIR__ . "/templates/messages.latte");
        $this->template->setTranslator($this->system->translator);

        // Sort messages according to priorities - 1. error, 2. warning, 3. info
        usort($this->template->flashes, function($next, $current) {

                    if ($current->type === "warning" && $next->type === "info" || $current->type === "error" && $next->type !== "error"
                    ) {
                        return +1;
                    }
                });

        $this->template->render();
    }

    /**
     * Render scripts
     */
    public function renderScripts()
    {
        $this->template->setFile(__DIR__ . "/templates/scripts.latte");
        $this->template->resUrl = $this->system->parameters["resUrl"];
        $this->template->render();
    }

    /**
     * Render toolbar
     */
    public function renderToolbar()
    {
        $this->template->setFile(__DIR__ . "/templates/toolbar.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->toolbarPlugins = array();
        foreach ($this->system->parameters["plugins"] as $plugin) {

            if (in_array("toolbar", $plugin["integration"])) {
                $this->template->toolbarPlugins[] = $plugin;
            }
        }
        $this->template->render();
    }

    /**
     * Callback for error event in form
     *
     * @param \Nette\Application\UI\Form $form Form instance
     *
     * @return void
     */
    public function onFormError(Form $form)
    {
        foreach ($form->errors as $error) {
            $this->flashMessage($error, "warning");
        }
    }

    /**
     * Control component factory
     *
     * @return \Nette\Application\UI\Multiplier
     */
    protected function createComponentControl()
    {
        $system = $this->system;
        $selectedFiles = $this->selectedFiles;
        return new Multiplier(function ($name) use ($system, $selectedFiles) {
                    $namespace = __NAMESPACE__;
                    $namespace .= "\\FileManager\Application\Controls";
                    $class = "$namespace\\$name";
                    return new $class($system, $selectedFiles);
                });
    }

    /**
     * Plugin component factory
     *
     * @return \Nette\Application\UI\Multiplier
     */
    protected function createComponentPlugin()
    {
        $system = $this->system;
        $selectedFiles = $this->selectedFiles;
        return new Multiplier(function ($name) use ($system, $selectedFiles) {

                    $class = $system->parameters["plugins"][$name]["class"];
                    return new $class($name, $system, $selectedFiles);
                });
    }

    /**
     * Get free space available
     *
     * @return integer
     */
    public function getFreeSpace()
    {
        if ($this->system->parameters["quota"]) {
            return $this->system->parameters["quotaLimit"] * 1048576 - $this->system->filesystem->getSize($this->system->parameters["dataDir"]);
        } else {
            return disk_free_space($this->system->parameters["dataDir"]);
        }
    }

    /**
     * Path validator
     *
     * @param string $dir  Dirname as relative path
     * @param string $file Filename (optional)
     *
     * @return boolean
     */
    public function isPathValid($dir, $file = null)
    {
        $path = $this->getAbsolutePath($dir);
        if ($file) {
            $path .= DIRECTORY_SEPARATOR . $file;
        }

        if (!file_exists($path)) {
            return false;
        }

        if ($this->system->parameters["dataDir"] === $path) {
            return true;
        }

        return $this->system->filesystem->isSubFolder($this->system->parameters["dataDir"], $path);
    }

    /**
     * Get absolute path from relative path
     *
     * @param string $actualdir Actual dir in relative format
     *
     * @return string
     */
    public function getAbsolutePath($actualdir)
    {
        return realpath($this->system->parameters["dataDir"] . $actualdir);
    }

}