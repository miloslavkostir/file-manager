<?php

class FileManager extends Nette\Application\UI\Control
{
    const NAME = "File Manager";

    const VERSION = '0.5 dev';

    /** @var string */
    protected $thumb = "__system_thumb";

    /** @var array */
    protected $core = array(
        'NewFolder',
        'rename'
    );

    /** @var array */
    public $config = array(
        'cache' => True,
        'readonly' => False,
        'resource_dir' => '/fm-src/',
        'quota' => False,
        'quota_limit' => 20,
        'imagemagick' => False,
        'lang' => 'en'
    );

    /** @var array */
    public $plugins;

    public function __construct()
    {
        parent::__construct();
        $plugins = new Plugins;
        $this->plugins = $plugins->loadPlugins();
    }

    public function handleMove()
    {
        $this['content']->handleMove();
    }

    public function handleRefreshContent()
    {
        $actualdir = $this['system']->getActualDir();

        if ($this->config['cache'] == True) {
            $this['caching']->deleteItem(NULL, array('tags' => 'treeview'));
            $this['caching']->deleteItem(array('content', $this['tools']->getRealPath($this['tools']->getAbsolutePath($actualdir))));
        }

        $this->handleShowContent($actualdir);
    }

    public function handleRunPlugin($plugin, $files = "")
    {
        $actualdir = $this['system']->getActualDir();

        if (in_array($plugin, $this->core) || $this['system']->isPlugin($this->plugins, $plugin)) {
                $this->template->plugin = $plugin;

                if ( property_exists($this[$plugin], 'files') )
                    $this[$plugin]->files = $files;

                $this->refreshSnippets(array(
                    'plugin',
                    'content',
                    'fileinfo'
                ));
        } else {
                $translator = $this['system']->getTranslator();
                $this->flashMessage(    // TODO message is missing in main template
                    $translator->translate('Plugin not found!'),
                    'warning'
                );
        }
    }

    public function handleShowFileInfo($filename)
    {
        $actualdir = $this['system']->getActualDir();

        if ($this['tools']->validPath($actualdir, $filename)) {
                $this->template->fileinfo = $actualdir;
                $this['fileInfo']->filename = $filename;
        }

       $this->invalidateControl('fileinfo');
    }

    public function handleShowContent($actualdir)
    {
        if ($this['tools']->validPath($actualdir)) {
                $this->template->content = $actualdir;

                $this['system']->setActualDir($actualdir);

                if ($this->presenter->isAjax())
                    $this->refreshSnippets(array(
                        'treeview',
                        'adressbar',
                        'toolbar',
                        'content',
                        'fileinfo',
                        'filter',
                        'clipboard',
                        'refreshButton',
                        'plugin'
                    ));
        }
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/FileManager.latte');
        $template->setTranslator($this['system']->getTranslator());

        if (!function_exists("exec"))
             throw new Exception ("Missing exec function! Application can not be loaded!");

        if(!@is_dir($this->config['uploadroot'] . $this->config['uploadpath']))
             throw new Exception ("Upload dir ".$this->config['uploadpath']." doesn't exist! Application can not be loaded!");

        if (!@is_writable($this->config['uploadroot'] . $this->config['uploadpath']))
             throw new Exception ("Upload dir " . $this->config['uploadroot'] . $this->config['uploadpath'] . " must be writable!");

        if(!@is_dir($this->presenter->context->params['wwwDir'] . $this->config['resource_dir']))
             throw new Exception ("Resource dir " . $this->config['resource_dir'] . " doesn't exist! Application can not be loaded!");

        $session = $this->presenter->context->session->getSection('file-manager');

        $clipboard = $session->clipboard;
        if (!empty($clipboard))
            $template->clipboard = $session->clipboard;

        if (empty($session->actualdir))
            $this->handleShowContent($this['tools']->getRootname());

        $plugins = $this->plugins;

        if (!empty($plugins)) {
            $toolbarPlugins = array();

            foreach($plugins as $plugin) {
                if ($plugin['toolbar'] == True)
                    $toolbarPlugins[] = $plugin;
            }

            if (!empty($toolbarPlugins))
                $template->plugins = $toolbarPlugins;
        }

        $this->refreshSnippets(array(
            'message',
            'diskusage'
        ));

        $template->render();
    }

    protected function refreshSnippets($snippets)
    {
        foreach ($snippets as $snippet)
            $this->invalidateControl($snippet);
    }

    /**
     * Global component factory
     *
     * @param	string	$name
     * @return	Component
     */
    protected function createComponent($name)
    {
            if ( !method_exists($this, 'createComponent'.$name) ) {
                    if ( class_exists($name) ) {
                            $class = new $name();
                            $class->config = $this->config;
                            return $class;
                    } else
                            throw new Exception('Can not create component ' . $name . '. Required class not found.');
            } else
                    return parent::createComponent($name);
    }
}