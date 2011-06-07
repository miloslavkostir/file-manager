<?php

use Nette\Application\UI\Control,
        Nette\Utils\Finder;

class FileManager extends Control
{
    const NAME = "File Manager";

    const VERSION = '0.5 dev';
    
    /** @var string */
    protected $thumb = "__system_thumb";

    /** @var array */
    protected $core = array(
        'NewFolder',
        'upload',
        'rename'
    );

    /** @var array */
    public $config = array(
        'cache' => True,
        'readonly' => False,
        'resource_dir' => '/fm-src/',
        'quota' => False,
        'quota_limit' => 20,
        'max_upload' => '1mb',
        'upload_resize' => False,
        'upload_resize_width' => 640,
        'upload_resize_height' => 480,
        'upload_resize_quality' => 90,
        'upload_filter' => False,
        'upload_chunk' => False,
        'upload_chunk_size' => '1mb',
        'imagemagick' => False,
        'lang' => 'en',
        'plugins' => ''
    );

    public function __construct()
    {
        parent::__construct();
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
            $this['caching']->deleteItem(array('content', realpath($this->getAbsolutePath($actualdir))));
        }

        $this->handleShowContent($actualdir);
    }

    public function handleRunPlugin($plugin, $files = "")
    {
        $actualdir = $this['system']->getActualDir();

        if (in_array($plugin, $this->core) || in_array($plugin, $this->config['plugins'])) {
                $this->template->plugin = $plugin;

                if ( property_exists($this[$plugin], 'actualdir') )
                    $this[$plugin]->actualdir = $actualdir;

                if ( property_exists($this[$plugin], 'files') )
                    $this[$plugin]->files = $files;

                $this->refreshSnippets(array(
                    'plugin',
                    'content',
                    'fileinfo'
                ));
        } else {
                $translator = parent::getParent()->getTranslator();
                $this->flashMessage(
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
        $template->setTranslator($this->getTranslator());

        if(!@is_dir($this->config['uploadroot'] . $this->config['uploadpath']))
             throw new Exception ("Upload dir ".$this->config['uploadpath']." doesn't exist! Application can not be loaded!");

        if (!@is_writable($this->config['uploadroot'] . $this->config['uploadpath']))
             throw new Exception ("Upload dir " . $this->config['uploadroot'] . $this->config['uploadpath'] . " must be writable!");

        if(!@is_dir(WWW_DIR . $this->config['resource_dir']))
             throw new Exception ("Resource dir " . $this->config['resource_dir'] . " doesn't exist! Application can not be loaded!");

        $namespace = $this->presenter->context->session->getNamespace('file-manager');

        $clipboard = $namespace->clipboard;
        if (!empty($clipboard ))
            $template->clipboard = $namespace->clipboard;

        if (empty($namespace->actualdir))
            $this->handleShowContent($this->getRootname());        
        
        $this->refreshSnippets(array(
            'message',
            'diskusage'
        ));

        $template->render();
    }

    protected function getRootname()
    {
        return array_pop((explode("/", trim($this->config['uploadpath'],"/"))));
    }

    protected function getAbsolutePath($actualdir)
    {
        if ($actualdir == $this->getRootname())
            return $this->config['uploadroot'] . $this->config['uploadpath'];
        else
            return $this->config['uploadroot'] . substr($this->config['uploadpath'], 0, -1) . $actualdir;
    }

    protected function refreshSnippets($snippets)
    {
        foreach ($snippets as $snippet)
            $this->invalidateControl($snippet);
    }

    public function getTranslator()
    {
        $lang = __DIR__ . '/lang/' . $this->config["lang"] . '.mo';
        if (file_exists($lang)) {
            $transl = new GettextTranslator($lang);
            return $transl;
        } else
            throw new Exception("Language file $lang does not exists!");
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