<?php

namespace Ixtrum;

use Ixtrum\FileManager\Application\FileSystem,
    Ixtrum\FileManager\Application\FileSystem\Finder,
    Nette\Application\UI\Multiplier;

class FileManager extends \Nette\Application\UI\Control
{

    const NAME = "iXtrum File Manager";
    const VERSION = "2.0 dev";

    /** @var \Ixtrum\FileManager\Application\Loader */
    protected $system;

    /** @var array */
    protected $selectedFiles = array();

    /** @var string */
    protected $defaultLang = "en";

    /**
     * Constructor
     *
     * @param \Nette\DI\Container $container System container
     * @param array               $config    User configuration
     */
    public function __construct(\Nette\DI\Container $container, $config = array())
    {
        parent::__construct();

        // Add important base parameters to config
        $config["wwwDir"] = $container->parameters["wwwDir"];
        $config["tempDir"] = $container->parameters["tempDir"];
        $config["appDir"] = __DIR__;

        // Load system container with services and configuration
        $this->system = new FileManager\Application\Loader($container->session, $config);
        $this->system->freeze();

        // Get & validate actual dir
        $actualDir = $this->system->session->get("actualdir");
        $actualPath = $this->getAbsolutePath($actualDir);
        if (!is_dir($actualPath) || empty($actualDir)) {
            // Set root dir as default
            $actualDir = $this->system->filesystem->getRootname();
        }
        $this->setActualDir($actualDir);

        // Get selected files via POST
        $selectedFiles = $container->httpRequest->getPost("files");
        if (is_array($selectedFiles)) {
            $this->selectedFiles = $selectedFiles;
        }

        $this->invalidateControl();
    }

    /**
     * New folder signal
     */
    public function handleNewFolder()
    {
        $this->template->newFolder = true;
    }

    /**
     * Rename signal
     */
    public function handleRename()
    {
        $this->template->rename = true;
    }

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

    public function handleRunPlugin($name)
    {
        // Find valid plugin
        foreach ($this->system->parameters["plugins"] as $plugin) {
            if ($name === $plugin["name"]) {
                $validPlugin = true;
            }
        }

        if (isset($validPlugin)) {
            $this->template->plugin = $name;
        } else {
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
     * Get available languages
     *
     * @return array
     */
    public function getLanguages()
    {
        $languages = array($this->defaultLang => $this->defaultLang);
        $files = Finder::findFiles("*.json")->in($this->system->parameters["appDir"] . $this->system->parameters["langDir"]);
        foreach ($files as $file) {

            $baseName = $file->getBasename(".json");
            $languages[$baseName] = $baseName;
        }
        return $languages;
    }

    /**
     * Get system parameters
     *
     * @return array
     */
    public function getSystemParameters()
    {
        return $this->system->parameters;
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

    public function render()
    {
        $this->template->setFile(__DIR__ . "/FileManager.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->resDir = $this->system->parameters["resDir"];

        // Get clipboard
        $clipboard = $this->system->session->get("clipboard");
        if ($clipboard) {
            $this->template->clipboard = $clipboard;
        }

        // Get theme
        $theme = $this->system->session->get("theme");
        if ($theme) {
            $this->template->theme = $theme;
        } else {
            $this->template->theme = "default";
        }

        // Get plugins
        $this->template->toolbarPlugins = array();
        $this->template->fileInfoPlugins = array();

        foreach ($this->system->parameters["plugins"] as $plugin) {

            if (in_array("toolbar", $plugin["integration"])) {
                $this->template->toolbarPlugins[] = $plugin;
            }
            if (in_array("fileinfo", $plugin["integration"])) {
                $this->template->fileInfoPlugins[] = $plugin;
            }
        }

        // Sort flash messages; 1=error, 2=warning, 3=info
        usort($this->template->flashes, function($flash, $nextFlash) {
                    return ($flash->type === "error") ? -1 : 1;
                });

        $this->template->render();
    }

    /**
     * Callback for error event in form
     *
     * @param \Nette\Application\UI\Form $form
     *
     * @return void
     */
    public function onFormError(\Nette\Application\UI\Form $form)
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
            return $this->system->parameters["quotaLimit"] * 1048576 - $this->system->filesystem->getSize($this->system->parameters["uploadroot"]);
        } else {
            return disk_free_space($this->system->parameters["uploadroot"]);
        }
    }

    /**
     * Synchronize all resources such as CSS, JS, images from 'resources'
     * directory located in file manager root to defined resource directory
     * located in web root.
     */
    public function syncResources()
    {
        $this->system->filesystem->copyDir(
                realpath($this->system->parameters["appDir"] . DIRECTORY_SEPARATOR . "resources"), $this->system->parameters["wwwDir"] . DIRECTORY_SEPARATOR . $this->system->parameters["resDir"]
        );
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

        if ($this->system->parameters["uploadroot"] === $path) {
            return true;
        }

        return $this->system->filesystem->isSubFolder($this->system->parameters["uploadroot"], $path);
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
        return realpath($this->system->parameters["uploadroot"] . $actualdir);
    }

}