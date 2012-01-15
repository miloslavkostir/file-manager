<?php

namespace Netfileman;

use Nette\DI\Container,
        Nette\Application\UI;


class Netfileman extends UI\Control
{
        const NAME = "Netfileman";

        const VERSION = "0.5 dev";


        /** @var Container */
        protected $context;

        /** @var array */
        protected $plugins;

        /** @var array */
        private $userConfig;


        public function __construct(array $userConfig)
        {
                parent::__construct();
                $this->userConfig = $userConfig;
                $this->monitor("Presenter");
        }


        protected function attached($presenter)
        {
                if ($presenter instanceof UI\Presenter) {

                        $this->context = new Services($this->presenter->context, $this->userConfig, __DIR__);
                        $this->plugins = $this->context->plugins->loadPlugins();
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


        public function handleRunPlugin($pluginName, $files = "")
        {
                // if sended by AJAX
                if (!$files)
                        $files = $this->presenter->context->httpRequest->getPost("files");

                $actualdir = $this->context->system->getActualDir();

                if ($this->context->plugins->isValidPlugin($pluginName, $this->plugins)) {

                        $this->template->plugin = $pluginName;
                        if (property_exists($this[$pluginName], "files"))
                                $this[$pluginName]->files = $files;
                } else {

                        $translator = $this->context->translator;
                        $this->flashMessage($translator->translate("Plugin '$pluginName' not found!"), "warning");
                }

                $this->handleShowContent($actualdir);
        }


        public function handleShowFileInfo($filename)
        {
                $actualdir = $this->context->system->getActualDir();

                if ($this->context->tools->validPath($actualdir, $filename)) {

                        $this->template->fileinfo = $actualdir;
                        $this["fileInfo"]->filename = $filename;
                }

                $this->invalidateControl("fileinfo");
        }


        public function handleShowContent($actualdir)
        {
                if ($this->context->tools->validPath($actualdir)) {

                        $this->template->content = $actualdir;
                        $this->context->system->setActualDir($actualdir);

                        if ($this->presenter->isAjax())
                                $this->refreshSnippets();
                }
        }


        public function render()
        {
                $template = $this->template;
                $template->setFile(__DIR__ . "/Netfileman.latte");
                $template->setTranslator($this->context->translator);

                $session = $this->presenter->context->session->getSection("file-manager");
                $clipboard = $session->clipboard;

                if ($clipboard)
                        $template->clipboard = $session->clipboard;

                if (!$template->content) {

                        if ($session->actualdir)
                                $template->content = $this->context->system->getActualDir();
                        else
                                $template->content = $this->context->tools->getRootname();
                }

                $plugins = $this->plugins;
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
                        $externalPlugins = $this->plugins;
                        if (isset($externalPlugins[$name]))
                                $namespace .= "\\Plugins";

                        $class = "$namespace\\$name";
                        if (class_exists($class)) {

                                $comp = new $class($this->userConfig);
                                return $comp;
                        } else
                                throw new \Nette\FileNotFoundException("Can not create component '$name'. Required class '$class' not found.");
                } else
                        return parent::createComponent($name);
        }
}