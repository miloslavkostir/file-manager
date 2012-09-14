<?php

namespace Ixtrum;

class FileManager extends \Nette\Application\UI\Control
{

    const NAME = "iXtrum File Manager";
    const VERSION = "0.5 dev";

    /** @var \Nette\DI\Container */
    protected $context;

    /**
     * Constructor
     *
     * @param \Nette\DI\Container $systemContainer System container
     * @param array               $config          User configuration
     */
    public function __construct(\Nette\DI\Container $systemContainer, $config = array())
    {
        parent::__construct();

        // Load system container with services and configuration
        $this->context = new FileManager\Services\Loader($systemContainer, $config, __DIR__);
        $this->context->freeze();

        // Get/set actual dir
        $actualdir = $this->context->application->getActualdir();
        if ($actualdir) {

            $actualPath = $this->context->filesystem->getAbsolutePath($actualdir);
            if (!is_dir($actualPath)) {
                $this->context->application->setActualdir(null);
            }
        }

        $this->invalidateControl();
    }

    public function handleRefreshContent()
    {
        $actualdir = $this->context->application->getActualDir();

        if ($this->context->parameters["cache"]) {

            $this->context->caching->deleteItem(NULL, array("tags" => "treeview"));
            $this->context->caching->deleteItem(array(
                "content",
                $this->context->filesystem->getRealPath(
                    $this->context->filesystem->getAbsolutePath($actualdir)
                )
            ));
        }

        $this->handleShowContent($actualdir);
    }

    public function handleRunPlugin($name, $files = "")
    {
        // if sended by AJAX
        if (!$files) {
            $files = $this->presenter->context->httpRequest->getPost("files");
        }

        // Find valid plugin
        foreach ($this->context->parameters["plugins"] as $plugin) {
            if ($name === $plugin["name"]) {
                $validPlugin = true;
            }
        }

        if (isset($validPlugin)) {
            $control = $this["plugin-$name"];
            if (property_exists($control, "files") && $files) {
                $control->files = $files;
            }

            $this->template->plugin = $name;
        } else {
            $this->flashMessage($this->context->translator->translate("Plugin '%s' not found!", $name), "warning");
        }
    }

    public function handleShowContent($actualdir)
    {
        if ($this->context->filesystem->validPath($actualdir)) {

            $this->template->content = $actualdir;
            $this->context->application->setActualDir($actualdir);
        } else {
            $this->flashMessage($this->context->translator->translate("Folder %s does not exist!", $actualdir), "warning");
        }
    }

    public function render()
    {
        \Nette\Diagnostics\Debugger::barDump($this, "Ixtrum File Manager");

        // Load resources
        if ($this->context->parameters["synchronizeResDir"] === true) {
            $resources = new FileManager\Application\Resources(
                    $this->context->parameters["wwwDir"] . $this->context->parameters["resDir"],
                    $this->context->parameters["rootPath"]
            );
            $resources->synchronize();
        }
        $this->template->resDir = $this->context->parameters["resDir"];

        $template = $this->template;
        $template->setFile(__DIR__ . "/FileManager.latte");
        $template->setTranslator($this->context->translator);

        $session = $this->presenter->context->session->getSection("file-manager");

        if ($session->clipboard) {
            $template->clipboard = $session->clipboard;
        }

        if ($session->theme) {
            $template->theme = $session->theme;
        } else {
            $template->theme = "default";
        }

        if (!isset($template->content)) {

            if ($session->actualdir) {
                $template->content = $session->actualdir;
            } else {

                $rootname = $this->context->filesystem->getRootname();
                $template->content = $rootname;
                $this->context->application->setActualDir($rootname);
            }
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
                $template->toolbarPlugins = $toolbarPlugins;
            }
            if (!empty($fileInfoPlugins)) {
                $template->fileInfoPlugins = $fileInfoPlugins;
            }
        }

        // Sort flash messages; 1=error, 2=warning, 3=info
        usort($template->flashes, function($flash, $nextFlash) {
                return ($flash->type === "error") ? -1 : 1;
            });

        $template->render();
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
        $container = $this->context->systemContainer;
        return new \Nette\Application\UI\Multiplier(function ($name) use ($container) {
                    $namespace = __NAMESPACE__;
                    $namespace .= "\\FileManager\Controls";
                    $class = "$namespace\\$name";
                    return new $class($container);
                });
    }

    /**
     * Plugin component factory
     *
     * @return \Nette\Application\UI\Multiplier
     */
    protected function createComponentPlugin()
    {
        $container = $this->context->systemContainer;
        return new \Nette\Application\UI\Multiplier(function ($name) use ($container) {
                    $namespace = __NAMESPACE__;
                    $namespace .= "\\FileManager\Plugins";
                    $class = "$namespace\\$name";
                    return new $class($container);
                });
    }

}