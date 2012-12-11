<?php

namespace Ixtrum;

use Ixtrum\FileManager\Application\FileSystem\Finder,
    Nette\Application\UI\Multiplier;

class FileManager extends \Nette\Application\UI\Control
{

    const NAME = "iXtrum File Manager";
    const VERSION = "1.0 beta";

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
        $actualPath = $this->system->filesystem->getAbsolutePath($actualDir);
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

            $this->system->caching->deleteItem(NULL, array("tags" => "treeview"));
            $this->system->caching->deleteItem(array(
                "content",
                realpath($this->system->filesystem->getAbsolutePath($this->getActualDir()))
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
     * Set actual dir
     *
     * @param string $dir relative dir path
     */
    public function setActualDir($dir)
    {
        if ($this->system->filesystem->validPath($dir)) {
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
        if ($this->system->parameters["plugins"]) {

            $toolbarPlugins = $fileInfoPlugins = array();
            foreach ($this->system->parameters["plugins"] as $plugin) {
                if ($plugin["toolbarPlugin"]) {
                    $toolbarPlugins[] = $plugin;
                }
                if ($plugin["fileInfoPlugin"]) {
                    $fileInfoPlugins[] = $plugin;
                }
            }

            if (!empty($toolbarPlugins)) {
                $this->template->toolbarPlugins = $toolbarPlugins;
            }
            if (!empty($fileInfoPlugins)) {
                $this->template->fileInfoPlugins = $fileInfoPlugins;
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
                            $namespace = __NAMESPACE__;
                            $namespace .= "\\FileManager\Application\Plugins";
                            $class = "$namespace\\$name";
                            return new $class($system, $selectedFiles);
                        });
    }

    /**
     * Get info about disk usage
     *
     * @return array
     */
    public function getDiskInfo()
    {
        return $this->system->filesystem->diskSizeInfo();
    }

}