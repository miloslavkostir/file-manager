<?php

use Nette\Environment;
use Nette\Application\UI\Control;
use Nette\Utils\Finder;

class FileManager extends Control
{
    const NAME = "File Manager";

    const VERSION = '0.5 dev';
    
    const DATE = '17.4.2011';

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
        'plugins' => array('fmPlayer'),
        'lang' => 'en'
    );

    public function __construct()
    {
        parent::__construct();
        $this->cache_path = TEMP_DIR . '/cache/_filemanager';
        $this->thumb = "__system_thumb";
    }
    
    public function handleRunPlugin($plugin, $actualdir)
    {
        $this->template->plugin = $plugin;
        $this[$plugin]->actualdir = $actualdir;
        $this->invalidateControl('plugin');
    }

    public function handleShowRename($actualdir, $filename)
    {
        if ($this['tools']->validPath($actualdir, $filename)) {
                        if ($this->config['readonly'] == True) {
                                        $translator = new GettextTranslator(__DIR__ . '/locale/FileManager.' . $this->config["lang"] . '.mo');
                                        $this->flashMessage(
                                                $translator->translate("File manager is in read-only mode"),
                                                'warning'
                                        );
                        } else {
                                $rename = array('actualdir' => $actualdir,
                                                'new_filename' => $filename,
                                                'orig_filename' => $filename);
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

    // TODO improve, because 2x calling clearFromCache can be little slower
    public function handleRefreshContent($actualdir)
    {
        $this['tools']->clearFromCache('fmtreeview');
        $this['tools']->clearFromCache(array('fmfiles', $actualdir));

        $this->handleShowContent($actualdir);
    }

    // TODO add move folder function & REFACTORING
    public function handleMoveFile($actualdir = "", $targetdir = "", $filename = "")
    {
        $translator = new GettextTranslator(__DIR__ . '/locale/FileManager.' . $this->config["lang"] . '.mo');

        if ($this->config['readonly'] == True)
                        $this->flashMessage(
                                $translator->translate("File manager is in read-only mode!"),
                                'warning'
                        );
        else {
                            // if sended by ajax (using drag&drop)
                            if ($actualdir == "" && $targetdir == "" && $filename == "") {
                                $request = Environment::getHttpRequest();
                                $actualdir = $request->getQuery('actualdir');
                                $targetdir = $request->getQuery('targetdir');
                                $filename = $request->getQuery('filename');
                            }

                            $actualpath = $this->getAbsolutePath($actualdir) . $filename;
                            $targetpath = $this->getAbsolutePath($targetdir);

                            if ($actualdir == $targetdir)

                                            $this->flashMessage(
                                                    $translator->translate('File can not be moved to same folder'),
                                                    'warning'
                                            );

                            elseif ( file_exists($targetpath . $filename) && file_exists($actualpath) && is_writable($actualpath) ) {

                                            $i = 1;
                                            while (file_exists($targetpath . '(' .$i . ')' . $filename)) {
                                                $i++;
                                            }

                                            copy($actualpath, $targetpath . '(' .$i . ')' . $filename);

                                            // refresh folder content cache
                                            $this['tools']->clearFromCache(array('fmfiles', $actualdir));
                                            $this['tools']->clearFromCache(array('fmfiles', $targetdir));

                                            $cache_file =  $this['fmFiles']->createThumbName($actualdir, $filename);
                                            if (file_exists($cache_file['path']))
                                                unlink($cache_file['path']);

                                            unlink($actualpath);

                                            $this->flashMessage(
                                                    $translator->translate('File was moved under new name, because file name in target directory already exists.'),
                                                    'warning'
                                            );

                                            $this['clipboard']->clearClipboard();


                                            $this->presenter->payload->result = 'success';

                                            $this->handleShowContent($actualdir);

                            } elseif ( file_exists($actualpath) && is_writable($actualpath) ) {

                                            copy($actualpath, $targetpath . $filename);
                                            
                                            // refresh folder content cache
                                            $this['tools']->clearFromCache(array('fmfiles', $actualdir));
                                            $this['tools']->clearFromCache(array('fmfiles', $targetdir));
                                            
                                            $cache_file =  $this['fmFiles']->createThumbName($actualdir, $filename);
                                            if (file_exists($cache_file['path']))
                                                unlink($cache_file['path']);

                                            unlink($actualpath);

                                             $this['clipboard']->clearClipboard();

                                            $this->flashMessage(
                                                    $translator->translate('File was succesfully moved'),
                                                    'info'
                                            );

                                            $this->presenter->payload->result = 'success';                                            
                                            $this->handleShowContent($targetdir);
                            } else {
                                            $this->flashMessage(
                                                    $translator->translate('An error occured. File was not moved.'),
                                                    'error'
                                            );
                                            $this->handleShowContent($actualdir);
                            }
        }
    }

    public function handleShowUpload($actualdir)
    {
        $translator = new GettextTranslator(__DIR__ . '/locale/FileManager.' . $this->config["lang"] . '.mo');

        $size = 0;
        $ok = True;

        foreach (Finder::findFiles('*')->from($this->config['uploadroot'] . $this->config['uploadpath']) as $file) {
                           $size += $file->getSize();
        }

        if ($this->config['quota'] == True) {
            $limit = $this->config['quota_limit'] * 1048576;
            $freespace = $limit - $size;
            $percentage = ($freespace / $limit)*100;
        } else {
            $freespace = disk_free_space($this->config['uploadroot']);
            $percentage = ($freespace / disk_total_space($this->config['uploadroot']))*100;
        }

        if ( $freespace <= 0 )
            $this->flashMessage(
                    $translator->translate("Disk is full! Files will not be uploaded"),
                    'warning'
            );
        elseif ($percentage <= 5)
            $this->flashMessage(
                        $translator->translate("Be careful, less than 5% of free space on disk left"),
                        'warning'
            );

        if ($this->config['readonly'] == True)
                    $this->flashMessage(
                                $translator->translate("File manager is in read-only mode. Files will not be uploaded"),
                                'warning'
                    );        

        /* check uploaddir */
        $path = $this->getAbsolutePath($actualdir);

        if ( !@is_dir($path)) {
                    $this->flashMessage(
                            $translator->translate('An error occurred. Upload path does not exist'),
                            'error'
                        );
                    $ok = False;
        }


        if ($ok == True) {
                $this->template->upload = $actualdir;
                
                $this['upload']->actualdir = $actualdir;

                if ($this->presenter->isAjax())
                    $this->refreshSnippets(array(
                        'newfolder',
                        'content',
                        'upload',
                        'fileinfo'
                    ));                        
        }

        if ($this->presenter->isAjax())
            $this->invalidateControl('rename');
    }

    public function handleShowFileInfo($actualdir, $filename)
    {
        $translator = new GettextTranslator(__DIR__ . '/locale/FileManager.' . $this->config["lang"] . '.mo');

        if ($this['tools']->validPath($actualdir, $filename)) {
                $this->template->fileinfo = $actualdir;
                $this['fileInfo']->actualdir = $actualdir;
                $this['fileInfo']->filename = $filename;
        }

       $this->handleShowContent($actualdir);
    }

    public function handleShowContent($actualdir)
    {       
        if ($this['tools']->validPath($actualdir)) {
                $this->template->content = $actualdir;
                $this->template->plugins = $this->config['plugins'];
                $this->template->actualdir = $actualdir;    // TODO some functions in template still depend on $actualdir in template

                $this['content']->actualdir = $actualdir;
                $this['filter']->actualdir = $actualdir;
                $this['clipboard']->actualdir = $actualdir;
                $this['navigation']->actualdir = $actualdir;
                $this['viewSelector']->actualdir = $actualdir;


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
                        'plugin',
                        'filter',
                        'clipboard',
                        'refreshButton'
                    ));
        }
    }

    public function handleShowAddNewFolder($actualdir)
    {       
        if ($this['tools']->validPath($actualdir)) {
                $this->template->newfolder = $actualdir;

                $this['newFolder']->actualdir = $actualdir;

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

    public function handleDeleteFolder($actualdir)
    {
        $translator = new GettextTranslator(__DIR__ . '/locale/FileManager.' . $this->config["lang"] . '.mo');
        
        if ($this->config['readonly'] == True)
                        $this->flashMessage(
                            $translator->translate('File manager is in read-only mode!'),
                            'warning'
                        );
        else {
                        if ($actualdir == $this->getRootname()) {
                            $path = $this->config['uploadroot'] . $this->config['uploadpath'];
                            $empty = True;  // only clear files in folder
                            $dir = null;    // because of delete sub-folders cache recursively
                        } else {
                            $path = $this->config['uploadroot'] . substr($this->config['uploadpath'], 0, -1) . $actualdir;
                            $empty = False; // delete folder completely
                            $dir = substr($actualdir, 0, -1);
                        }
                        
                        if ($this['tools']->validPath($actualdir)) {

                                            // delete sub-folders cache recursively
                                            $dirs = $this['treeview']->getDirTree($path);
                                            $this['tools']->clearDirCache($dirs, $dir);
                                            
                                            if ($this['fmFiles']->deleteFolder($path, $empty) == true) {

                                                            // TODO clear old folder content cache recursively
                                                            // refresh cache
                                                            $this['tools']->clearFromCache('fmtreeview');
                                                            $this['tools']->clearFromCache(array('fmfiles', $actualdir));

                                                            // clear cache in parent directory                                                             
                                                            if (dirname($actualdir) == '\\')
                                                                $this['tools']->clearFromCache(array('fmfiles', $this->getRootname()));
                                                            else
                                                                $this['tools']->clearFromCache(array('fmfiles', dirname($actualdir). '/'));

                                                            $this['clipboard']->clearClipboard();

                                                            $this->flashMessage(
                                                                $translator->translate('Folder succesfully deleted'),
                                                                'info'
                                                            );

                                                            $this['content']->handleGoToParent($actualdir);
                                                            
                                                } else
                                                            $this->flashMessage(
                                                                $translator->translate('An error occurred, folder was not completely deleted.'),
                                                                'error'
                                                            );
                        }                            
        }


        $this->invalidateControl('rename');
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/FMTemplate.latte');

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

        $template->fmversion = self::VERSION;
        $template->fmdate = self::DATE;
        $template->config = $this->config;
        $template->rootname = $this->getRootname();

        $template->clipboard = Environment::getSession('file-manager')->clipboard;

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

    public function createComponentTools()
    {
        $tools = new FMTools;
        $tools->config = $this->config;
        return $tools;
    }

    public function createComponentFmPlayer()
    {
        $player = new FMPlayer;
        return $player;
    }

    public function createComponentFmFiles()
    {
        $f = new FMFiles;
        $f->config = $this->config;
        $f->thumb = $this->thumb;
        return $f;
    }

    public function createComponentNavigation()
    {
        $nav = new FMNavigation;
        $nav->config = $this->config;
        return $nav;
    }

    public function createComponentUpload()
    {
        $up = new FMUpload;
        $up->config = $this->config;
        return $up;
    }

    public function createComponentNewFolder()
    {
        $nf = new FMNewFolder;
        $nf->config = $this->config;
        return $nf;
    }

    public function createComponentRename()
    {
        $r = new FMRename;
        $r->config = $this->config;
        return $r;
    }

    public function createComponentContent()
    {
        $c = new FMContent;
        $c->config = $this->config;
        return $c;
    }

    public function createComponentFileInfo()
    {
        $fi = new FMFileInfo;
        $fi->config = $this->config;
        return $fi;
    }

    public function createComponentDiskUsage()
    {
        $du = new FMDiskUsage;
        $du->config = $this->config;
        return $du;
    }

    public function createComponentTreeview()
    {
        $t = new FMTreeview;
        $t->config = $this->config;
        return $t;
    }

    public function createComponentClipboard()
    {
        $c = new FMClipboard;
        $c->config = $this->config;
        return $c;
    }

    public function createComponentFilter()
    {
        $f = new FMFilter;
        $f->config = $this->config;
        return $f;
    }

    public function createComponentViewSelector()
    {
        $sv = new FMViewSelector;
        $sv->config = $this->config;
        return $sv;
    }
}