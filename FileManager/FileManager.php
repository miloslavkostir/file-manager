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


        public function __construct($userConfig = array())
        {
                parent::__construct();
                $this->userConfig = $userConfig;
                $this->monitor("Presenter");
        }


        protected function attached($presenter)
        {
                if ($presenter instanceof UI\Presenter) {

                        $this->context = new FileManager\Services\Loader($this->presenter->context, $this->userConfig, __DIR__);

                        $system = $this->context->system;
                        $actualdir = $system->getActualdir();
                        if ($actualdir) {

                                $actualPath = $this->context->tools->getAbsolutePath($actualdir);
                                if (!is_dir($actualPath))
                                        $system->setActualdir(null);
                        }

                        $this->refreshSnippets(array("message", "diskusage"));
                }

                parent::attached($presenter);
        }


        public function handleMove()
        {
                $this["content"]->handleMove();
        }


        public function handleRefreshContent()
        {
                $actualdir = $this->context->system->getActualDir();

                if ($this->context->parameters["cache"]) {

                        $tools = $this->context->tools;
                        $caching = $this->context->caching;
                        $caching->deleteItem(NULL, array("tags" => "treeview"));
                        $caching->deleteItem(array("content", $tools->getRealPath($tools->getAbsolutePath($actualdir))));
                }

                $this->handleShowContent($actualdir);
        }


        public function handleRunPlugin($plugin, $files = "")
        {
                // if sended by AJAX
                if (!$files)
                        $files = $this->presenter->context->httpRequest->getPost("files");

                if ($this->context->plugins->isValidControl($plugin)) {

                        if (property_exists($this[$plugin], "files") && $files)
                                $this[$plugin]->files = $files;

                        $this->template->plugin = $plugin;
                        $this->refreshSnippets(array("plugin"));
                } else
                        $this->flashMessage($this->context->translator->translate("Plugin '%s' not found!", $plugin), "warning");
        }


        public function handleShowFileInfo($filename = "")
        {
                // if sended by AJAX
                if (!$filename)
                        $filename = $this->presenter->context->httpRequest->getQuery("filename");

                if ($filename) {

                        $actualdir = $this->context->system->getActualDir();
                        if ($this->context->tools->validPath($actualdir, $filename)) {

                                $this->template->fileinfo = $actualdir;
                                $this["fileInfo"]->filename = $filename;
                                $this->invalidateControl("fileinfo");
                        } else
                                $this->flashMessage($this->context->translator->translate("File not found!"), "warning");      
                } else
                        $this->flashMessage($this->context->translator->translate("Incorrect input data!"), "error");
        }


        public function handleShowContent($actualdir)
        {
                if ($this->context->tools->validPath($actualdir)) {

                        $this->template->content = $actualdir;
                        $this->context->system->setActualDir($actualdir);

                        if ($this->presenter->isAjax())
                                $this->refreshSnippets();
                } else
                        $this->flashMessage($this->context->translator->translate("Folder %s does not exists!", $actualdir), "warning");
        }


        public function render()
        {
                $template = $this->template;
                $template->setFile(__DIR__ . "/FileManager.latte");
                $template->setTranslator($this->context->translator);

                $session = $this->presenter->context->session->getSection("file-manager");
                $clipboard = $session->clipboard;

                if ($clipboard)
                        $template->clipboard = $session->clipboard;

                if (!isset($template->content)) {

                        if ($session->actualdir)
                                $template->content = $session->actualdir;
                        else {

                                $rootname = $this->context->tools->getRootname();
                                $template->content = $rootname;
                                $this->context->system->setActualDir($rootname);
                        }
                }

                $plugins = $this->context->plugins->loadPlugins();
                if ($plugins) {

                        $toolbarPlugins = array();
                        foreach($plugins as $plugin) {

                                if ($plugin["toolbarPlugin"])
                                        $toolbarPlugins[] = $plugin;
                        }

                        if ($toolbarPlugins)
                                $template->plugins = $toolbarPlugins;
                }

                $template->render();
        }


        protected function refreshSnippets($snippets = "")
        {
                if (!$snippets)
                        $this->invalidateControl();
                elseif (is_array($snippets)) {

                        foreach ($snippets as $snippet) {
                                $this->invalidateControl($snippet);
                        }
                } else
                        throw new \Nette\InvalidArgumentException("Not supported parameter for snippet refresh");
        }


        /**
         * Global component factory
         *
         * @param	string	$name
         * @return	Component
         */
        protected function createComponent($name)
        {
                if (!method_exists($this, "createComponent$name")) {

                        $namespace = __NAMESPACE__;

                        $plugins = $this->context->plugins->getPluginNames();
                        if (in_array($name, $plugins))
                                $namespace .= "\\FileManager\Plugins";
                        else
                                $namespace .= "\\FileManager\Controls";

                        $class = "$namespace\\$name";
                        if (class_exists($class)) {

                                $component = new $class($this->userConfig);
                                return $component;
                        } else
                                throw new \Nette\FileNotFoundException("Can not create component '$name'. Required class '$class' not found.");
                } else
                        return parent::createComponent($name);
        }
}