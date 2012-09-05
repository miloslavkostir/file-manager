<?php

namespace Ixtrum;

use Nette\DI\Container,
    Nette\Application\UI;

class FileManager extends UI\Control
{

    const NAME = "iXtrum File Manager";
    const VERSION = "0.5 dev";

    /** @var Container */
    protected $context;

    /** @var array */
    private $userConfig;

    /** @var array */
    protected $plugins;

    public function __construct($userConfig = array())
    {
        parent::__construct();
        $this->userConfig = $userConfig;
        $this->monitor("Presenter");
    }

    /**
     * @param Nette\Application\UI\Presenter $presenter
     */
    protected function attached($presenter)
    {
        if ($presenter instanceof UI\Presenter) {

            // Load system configuration
            $this->context = new FileManager\Services\Loader(
                    $this->presenter->context,
                    $this->userConfig,
                    __DIR__
            );
            $this->context->freeze();

            // Get/set actual dir
            $actualdir = $this->context->application->getActualdir();
            if ($actualdir) {

                $actualPath = $this->context->filesystem->getAbsolutePath($actualdir);
                if (!is_dir($actualPath)) {
                    $this->context->application->setActualdir(null);
                }
            }

            // Load plugins
            $this->plugins = $this->context->plugins->loadPlugins();

            $this->refreshSnippets(array("message", "diskusage"));
        }

        parent::attached($presenter);
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

        if ($this->context->plugins->isValidPlugin($name)) {
            $control =  $this["plugin-$name"];
            if (property_exists($control, "files") && $files) {
                $control->files = $files;
            }

            $this->template->plugin = $name;
            $this->refreshSnippets(array("plugin"));
        } else {
            $this->flashMessage($this->context->translator->translate("Plugin '%s' not found!", $name), "warning");
        }
    }

    public function handleShowContent($actualdir)
    {
        if ($this->context->filesystem->validPath($actualdir)) {

            $this->template->content = $actualdir;
            $this->context->application->setActualDir($actualdir);

            if ($this->presenter->isAjax()) {
                $this->refreshSnippets();
            }
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
                    $this->context->parameters["resPath"],
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

        // Load plugins
        if ($this->plugins) {

            $toolbarPlugins = $fileInfoPlugins = array();
            foreach ($this->plugins as $plugin) {
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
     * Invalidate controls
     *
     * @param array $snippets
     * @throws \Nette\InvalidArgumentException
     */
    public function refreshSnippets($snippets = array())
    {
        if (!$snippets) {
            $this->invalidateControl();
        } elseif (is_array($snippets)) {

            foreach ($snippets as $snippet) {
                $this->invalidateControl($snippet);
            }
        } else {
            throw new \Nette\InvalidArgumentException("Not supported parameter for snippet refresh");
        }
    }

    /**
     * Callback for error event in form
     *
     * @param \Nette\Application\UI\Form $form
     * @return void
     */
    public function onFormError(\Nette\Application\UI\Form $form)
    {
        foreach ($form->errors as $error) {
            $this->flashMessage($error, "warning");
        }
    }

    protected function createComponentControl()
    {
        $config = $this->userConfig;
        return new \Nette\Application\UI\Multiplier(function ($name) use ($config) {
            $namespace = __NAMESPACE__;
            $namespace .= "\\FileManager\Controls";
            $class = "$namespace\\$name";
            return new $class($config);
        });
    }

    protected function createComponentPlugin()
    {
        $config = $this->userConfig;
        return new \Nette\Application\UI\Multiplier(function ($name) use ($config) {
            $namespace = __NAMESPACE__;
            $namespace .= "\\FileManager\Plugins";
            $class = "$namespace\\$name";
            return new $class($config);
        });
    }

}