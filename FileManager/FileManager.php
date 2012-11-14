<?php

namespace Ixtrum;

class FileManager extends \Nette\Application\UI\Control
{

    const NAME = "iXtrum File Manager";
    const VERSION = "0.5 dev";

    /** @var \Nette\DI\Container */
    protected $context;

    /** @var array */
    protected $selectedFiles = array();

    /** @var string */
    protected $view;

    /** @var array */
    private $userConfig;

    /** @var \Nette\DI\Container */
    private $systemContainer;

    /**
     * Constructor
     *
     * @param \Nette\DI\Container $container System container
     * @param array               $config    User configuration
     */
    public function __construct(\Nette\DI\Container $container, $config = array())
    {
        parent::__construct();

        // Set up class properties
        $this->userConfig = $config;
        $this->systemContainer = $container;

        // Load system container with services and configuration
        $this->context = new FileManager\Application\Loader($container, $config, __DIR__);
        $this->context->freeze();

        // Get & validate actual dir
        $actualDir = $this->context->session->get("actualdir");
        $actualPath = $this->context->filesystem->getAbsolutePath($actualDir);
        if (!is_dir($actualPath) || empty($actualDir)) {
            // Set root dir as default
            $actualDir = $this->context->filesystem->getRootname();
        }
        $this->setActualDir($actualDir);

        // Get selected files via POST
        $selectedFiles = $container->httpRequest->getPost("files");
        if (is_array($selectedFiles)) {
            $this->selectedFiles = $selectedFiles;
        }

        // Get & validate selected view
        $view = $this->context->session->get("view");
        $allowedViews = array("details", "large", "list", "small");
        if (!empty($view) && in_array($view, $allowedViews)) {
            $this->view = $view;
        } else {
            $this->view = "large";
        }

        $this->invalidateControl();
    }

    public function handleRefreshContent()
    {
        if ($this->context->parameters["cache"]) {

            $this->context->caching->deleteItem(NULL, array("tags" => "treeview"));
            $this->context->caching->deleteItem(array(
                "content",
                $this->context->filesystem->getRealPath(
                    $this->context->filesystem->getAbsolutePath($this->getActualDir())
                )
            ));
        }
    }

    public function handleRunPlugin($name)
    {
        // Find valid plugin
        foreach ($this->context->parameters["plugins"] as $plugin) {
            if ($name === $plugin["name"]) {
                $validPlugin = true;
            }
        }

        if (isset($validPlugin)) {
            $this->template->plugin = $name;
        } else {
            $this->flashMessage($this->context->translator->translate("Plugin '%s' not found!", $name), "warning");
        }
    }

    /**
     * Getter for actualDir
     *
     * @return string Actual directory
     */
    public function getActualDir()
    {
        return $this->context->session->get("actualdir");
    }

    /**
     * Setter for actualDir
     *
     * @param string $dir relative dir path
     */
    public function setActualDir($dir)
    {
        if ($this->context->filesystem->validPath($dir)) {
            $this->context->session->set("actualdir", $dir);
        } else {
            $this->flashMessage($this->context->translator->translate("Folder %s does not exist!", $dir), "warning");
        }
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . "/FileManager.latte");
        $this->template->setTranslator($this->context->translator);

        // Load resources
        if ($this->context->parameters["synchronizeResDir"] === true) {
            $resources = new FileManager\Application\Resources(
                    $this->context->parameters["wwwDir"] . $this->context->parameters["resDir"],
                    $this->context->parameters["appPath"]
            );
            $resources->synchronize();
        }
        $this->template->resDir = $this->context->parameters["resDir"];

        // Get clipboard
        $clipboard = $this->context->session->get("clipboard");
        if ($clipboard) {
            $this->template->clipboard = $clipboard;
        }

        // Get theme
        $theme = $this->context->session->get("theme");
        if ($theme) {
            $this->template->theme = $theme;
        } else {
            $this->template->theme = "default";
        }

        // Get plugins
        if ($this->context->parameters["plugins"]) {

            $toolbarPlugins = $fileInfoPlugins = array();
            foreach ($this->context->parameters["plugins"] as $plugin) {
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
        $container = $this->systemContainer;
        $config = $this->userConfig;
        return new \Nette\Application\UI\Multiplier(function ($name) use ($container, $config) {
                    $namespace = __NAMESPACE__;
                    $namespace .= "\\FileManager\Application\Controls";
                    $class = "$namespace\\$name";
                    return new $class($container, $config);
                });
    }

    /**
     * Plugin component factory
     *
     * @return \Nette\Application\UI\Multiplier
     */
    protected function createComponentPlugin()
    {
        $container = $this->systemContainer;
        $config = $this->userConfig;
        return new \Nette\Application\UI\Multiplier(function ($name) use ($container, $config) {
                    $namespace = __NAMESPACE__;
                    $namespace .= "\\FileManager\Plugins";
                    $class = "$namespace\\$name";
                    return new $class($container, $config);
                });
    }

    /**
     * Get info about disk usage
     *
     * @return array
     */
    public function getDiskInfo()
    {
        return $this->context->filesystem->diskSizeInfo();
    }

}