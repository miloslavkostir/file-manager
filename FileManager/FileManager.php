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

            $this->context = new FileManager\Services\Loader($this->presenter->context, $this->userConfig, __DIR__);
            $this->context->freeze();

            $actualdir = $this->context->application->getActualdir();
            if ($actualdir) {

                $actualPath = $this->context->filesystem->getAbsolutePath($actualdir);
                if (!is_dir($actualPath)) {
                    $this->context->application->setActualdir(null);
                }
            }

            $this->plugins = $this->context->plugins->loadPlugins();

            $this->refreshSnippets(array("message", "diskusage"));
        }

        parent::attached($presenter);
    }

    public function handleRefreshContent()
    {
        $actualdir = $this->context->application->getActualDir();

        if ($this->context->parameters["cache"]) {

            $filesystem = $this->context->filesystem;
            $caching = $this->context->caching;
            $caching->deleteItem(NULL, array("tags" => "treeview"));
            $caching->deleteItem(array("content", $filesystem->getRealPath($filesystem->getAbsolutePath($actualdir))));
        }

        $this->handleShowContent($actualdir);
    }

    public function handleRunPlugin($plugin, $files = "")
    {
        // if sended by AJAX
        if (!$files) {
            $files = $this->presenter->context->httpRequest->getPost("files");
        }

        if ($this->context->plugins->isValidControl($plugin)) {

            if (property_exists($this[$plugin], "files") && $files) {
                $this[$plugin]->files = $files;
            }

            $this->template->plugin = $plugin;
            $this->refreshSnippets(array("plugin"));
        } else {
            $this->flashMessage($this->context->translator->translate("Plugin '%s' not found!", $plugin), "warning");
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
        $template = $this->template;
        $template->setFile(__DIR__ . "/FileManager.latte");
        $template->setTranslator($this->context->translator);

        $session = $this->presenter->context->session->getSection("file-manager");
        $clipboard = $session->clipboard;

        if ($clipboard) {
            $template->clipboard = $session->clipboard;
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

    /**
     * Global component factory
     *
     * @param string $name component name
     * @return IComponent created component
     */
    protected function createComponent($name)
    {
        if (!method_exists($this, "createComponent$name")) {

            $namespace = __NAMESPACE__;

            $plugins = $this->context->plugins->getPluginNames();
            if (in_array($name, $plugins)) {
                $namespace .= "\\FileManager\Plugins";
            } else {
                $namespace .= "\\FileManager\Controls";
            }

            $class = "$namespace\\$name";
            if (class_exists($class)) {

                $component = new $class($this->userConfig);
                return $component;
            } else {
                throw new \Nette\FileNotFoundException("Can not create component '$name'. Required class '$class' not found.");
            }
        } else {
            return parent::createComponent($name);
        }
    }

}