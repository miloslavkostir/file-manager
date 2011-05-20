<?php

use Nette\Environment;
use Nette\Application\UI\Control;
use Nette\Utils\Finder;

class FileManager extends Control
{
    const NAME = "File Manager";

    const VERSION = '0.5 dev';
    
    /** @var string */
    protected $cache_path;

    /**
     * @var string
     * Prefix for thumb folders and thumbnails
     */
    protected $thumb;

    /** @var array */
    public $config = array(
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
        'lang' => 'en'
    );

    public function __construct()
    {
        parent::__construct();
        $this->cache_path = TEMP_DIR . '/cache/_filemanager';
        $this->thumb = "__system_thumb";
    }
    
    public function handleShowRename($filename)
    {
        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;
        
        if ($this['tools']->validPath($actualdir, $filename)) {
                        if ($this->config['readonly'] == True) {
                                        $translator = new GettextTranslator(__DIR__ . '/locale/FileManager.' . $this->config["lang"] . '.mo');
                                        $this->flashMessage(
                                                $translator->translate("File manager is in read-only mode"),
                                                'warning'
                                        );
                        } else {
                                $rename = array(
                                    'new_filename' => $filename,
                                    'orig_filename' => $filename
                                );
                                $this->template->rename = $rename;
                                $this['rename']->params = $rename;

                                if ($this->presenter->isAjax())
                                    $this->invalidateControl('rename');
                        }

                        if ($this->presenter->isAjax())
                            $this->refreshSnippets(array(
                                'content',
                                'fileinfo'
                            ));
        }
    }
    
    public function handleMove()
    {
        $this['content']->handleMove();
    }

    // TODO improve, because 2x calling clearFromCache can be little slower
    public function handleRefreshContent()
    {
        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;

        $this['tools']->clearFromCache('fmtreeview');
        $this['tools']->clearFromCache(array('fmfiles', $actualdir));

        $this->handleShowContent($actualdir);
    }

    public function handleShowUpload()
    {
        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir; 

        $this->template->upload = $actualdir;        

        if ($this->presenter->isAjax())
            $this->refreshSnippets(array(
                'newfolder',
                'content',
                'upload',
                'fileinfo',
                'rename'
            ));
    }

    public function handleShowFileInfo($filename)
    {
        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;

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
                $this->template->actualdir = $actualdir;

                // set actualdir
                $namespace = Environment::getSession('file-manager');
                $namespace->actualdir = $actualdir;

                if ($this->presenter->isAjax())
                    $this->refreshSnippets(array(
                        'treeview',
                        'adressbar',
                        'toolbar',
                        'newfolder',
                        'content',
                        'upload',
                        'fileinfo',
                        'rename',
                        'filter',
                        'clipboard',
                        'refreshButton'
                    ));
        }
    }

    public function handleShowAddNewFolder()
    {
        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;
        
        if ($this['tools']->validPath($actualdir)) {
                $this->template->newfolder = $actualdir;

                if ($this->presenter->isAjax())
                    $this->refreshSnippets(array(
                        'content',
                        'newfolder',
                        'upload',
                        'fileinfo',
                        'rename'
                    ));
                
        }
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/FileManager.latte');

        if(!@is_dir($this->config['uploadroot'] . $this->config['uploadpath']))
             throw new Exception ("Upload dir ".$this->config['uploadpath']." doesn't exist! Application can not be loaded!");

        if (!@is_writable($this->config['uploadroot'] . $this->config['uploadpath']))
             throw new Exception ("Upload dir " . $this->config['uploadroot'] . $this->config['uploadpath'] . " must be writable!");

        if(!@is_dir(WWW_DIR . $this->config['resource_dir']))
             throw new Exception ("Resource dir " . $this->config['resource_dir'] . " doesn't exist! Application can not be loaded!");

        // set language
        $lang_file = __DIR__ . '/locale/FileManager.'.$this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");
       
        $cache_const = md5($this->config['uploadroot'] . $this->config['uploadpath']);
        $cache_dir = $this->cache_path . $cache_const;
        if(!@is_dir($cache_dir)) {
            $oldumask = umask(0);
            mkdir($cache_dir, 0777);
            umask($oldumask);
        }

        $namespace = Environment::getSession('file-manager');
        
        $template->config = $this->config;
        $template->rootname = $this->getRootname();
        $template->clipboard = $namespace->clipboard;

        $actualdir = $namespace->actualdir;
        if (empty($actualdir))
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

    /**
     * Global component factory
     *
     * @param	string	$name
     * @return	Control
     */
    protected function createComponent($name)
    {
            $className = ucfirst($name);
            if ( !method_exists($this, "createComponent$className") ) {
                    if ( class_exists($className) ) {
                            $class = new $className();
                            $class->config = $this->config;
                            return $class;
                    } else
                            throw new Exception("Can not create component '$name'. Required class '$name' not found.");
            } else
                return parent::createComponent($name);
    }
}