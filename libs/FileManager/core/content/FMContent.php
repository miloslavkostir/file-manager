<?php

use Nette\Application\Responses\FileResponse;
use Nette\Environment;
use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
use Nette\Image;
use Nette\Utils\Finder;

class FMContent extends FileManager
{
    /** @var array */
    public $config;

    /** @var string */
    public $mask;

    public function __construct()
    {
        parent::__construct();
    }
    
    public function handleShowFileInfo($filename = "")
    {
        // if sended by AJAX
        if (empty($filename)) {
            $request = Environment::getHttpRequest();
            $filename = $request->getQuery('filename');
        }
        parent::getParent()->handleShowFileInfo($filename);
    }

    public function handleCopyToClipboard($filename = "")
    {
        // if sended by AJAX
        if (empty($filename)) {
            $request = Environment::getHttpRequest();
            $filename = $request->getQuery('filename');
        }

        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;
        $namespace->clipboard[$actualdir.$filename] = array(
            'action' => 'copy',
            'actualdir' => $actualdir,
            'filename' => $filename
        );

        $this->handleShowContent($actualdir);
    }

    public function handleCutToClipboard($filename = "")
    {
        // if sended by AJAX
        if (empty($filename)) {
            $request = Environment::getHttpRequest();
            $filename = $request->getQuery('filename');
        }

        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;
        
        if ($this['tools']->validPath($actualdir, $filename)) {
            $namespace->clipboard[$actualdir.$filename] = array(
                'action' => 'cut',
                'actualdir' => $actualdir,
                'filename' => $filename
            );

            $this->handleShowContent($actualdir);
        }
    }

    public function handleDeleteFile($filename = "")
    {
        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;
        $translator = new GettextTranslator(__DIR__ . '/../../locale/FileManager.' . $this->config["lang"] . '.mo');

        // if sended by AJAX
        if (empty($filename)) {
            $request = Environment::getHttpRequest();
            $filename = $request->getQuery('filename');
        }

        if ($this->config['readonly'] == True)
                        parent::getParent()->flashMessage(
                                $translator->translate("File manager is in read-only mode"),
                                'warning'
                        );
        else {
                        $path = parent::getParent()->getAbsolutePath($actualdir);

                        if ($this['tools']->validPath($actualdir, $filename)) {

                            if (is_writable($path . $filename)) {
                                    $cache_file =  $this['fmFiles']->createThumbName($actualdir, $filename);

                                    // delete thumb
                                    if ( file_exists($cache_file['path']) && is_writable($path . $filename) )
                                           unlink($cache_file['path']);

                                    // delete source file
                                    if (@unlink($path . $filename))
                                            parent::getParent()->flashMessage(
                                                    $translator->translate('File was successfuly deleted'),
                                                    'info'
                                            );
                                    else
                                            parent::getParent()->flashMessage(
                                                    $translator->translate('An error occurred during deleting file.'),
                                                    'error'
                                            );
                                            
                                     $this['clipboard']->clearClipboard();
                            } else
                                    parent::getParent()->flashMessage(
                                            $translator->translate('Permission denied.'),
                                            'warning'
                                    );

                        }

                        // refresh folder content cache
                        $this['tools']->clearFromCache(array('fmfiles', $actualdir));
                        $this->handleShowContent($actualdir);
        }
    }
    
    public function handleDownloadFile($filename = "")
    {
        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;
        
        // if sended by AJAX
        if (empty($filename)) {
            $request = Environment::getHttpRequest();
            $filename = $request->getQuery('filename');
        }

        if ($this['tools']->validPath($actualdir, $filename)) {
            $path = parent::getParent()->getAbsolutePath($actualdir) . $filename;
            $this->presenter->sendResponse(new FileResponse($path, NULL, NULL));
        }
    }

    public function handleGoToParent($actualdir)
    {
        $parent = dirname($actualdir);

        if ($parent == '\\' || $parent == '.')
            $parent_path = $this->getRootname();
        else
            $parent_path = $parent . '/';

        $this->handleShowContent($parent_path);
    }

    public function handleMoveFile($actualdir = "", $targetdir = "", $filename = "")
    {
        parent::getParent()->handleMoveFile($actualdir = "", $targetdir = "", $filename = "");
    }

    public function handleShowFullImage($actualdir, $filename)
    {
        if ($this['tools']->validPath($actualdir, $filename)) {
            $path = parent::getParent()->getAbsolutePath($actualdir) . $filename;
            $image = Image::fromFile($path);
            $image->send();
        }
    }

    public function handleShowContent($actualdir) {
        parent::getParent()->handleShowContent($actualdir);
    }

    public function handleShowRename($filename = "") {
        // if sended by AJAX
        if (empty($filename)) {
            $request = Environment::getHttpRequest();
            $filename = $request->getQuery('filename');
        }
        parent::getParent()->handleShowRename($filename);
    }

    public function handleShowThumb($dir, $file)
    {
        $path = parent::getParent()->getAbsolutePath($dir) . $file;

        $cache_file =  $this['fmFiles']->createThumbName($dir, $file);

        if ( file_exists($cache_file['path']) ) {
                    $image = Image::fromFile($cache_file['path']);
                    $image->send();
        } else {
                    $disksize = $this['tools']->diskSizeInfo();
                    if ($disksize['spaceleft'] > 2 ) {
                            $image = Image::fromFile($path);
                            $image->resize(96, NULL);
                            $image->save($cache_file['path'], 80);
                            $image->send();
                    }
        }
    }

    public function render()
    {
        $translator = new GettextTranslator(__DIR__ . '/../../locale/FileManager.' . $this->config["lang"] . '.mo');
        $namespace = Environment::getSession('file-manager');

        $actualdir = $namespace->actualdir;
        $template = $this->template;
        
        $view = $namespace->view;

        if (!empty($view)) {
            $c_template = __DIR__ . '/' . $view . '.latte';
            if (file_exists($c_template))
                $template->setFile($c_template);
            else {
                $template->setFile(__DIR__ . '/large.latte');
                parent::getParent()->flashMEssage(
                            $translator->translate('Unknown view selected.'),
                            'warning'
                        );
                $view = 'large';
            }
        } else {
                $template->setFile(__DIR__ . '/large.latte');
                $view = 'large';
        }

        // set language
        $lang_file = __DIR__ . '/../../locale/FileManager.'. $this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");

        $cache_const = md5($this->config['uploadroot'] . $this->config['uploadpath']);
        $cache_dir = parent::getParent()->cache_path . $cache_const;
        $storage = new FileStorage($cache_dir);
        $cache = new Cache($storage);

        if (empty($this->mask)) {
                    if (isset($cache[array('fmfiles', $actualdir)]))
                        $output = $cache[array('fmfiles', $actualdir)];
                    else {
                        $output = $this->getDirectoryContent($actualdir, '*', $view);
                        $cache->save(array('fmfiles', $actualdir), $output);
                    }
        } else
                    $output = $this->getDirectoryContent($actualdir, $this->mask, $view);

        $template->files = $output;
        $template->config = $this->config;
        $template->actualdir = $actualdir;
        $template->rootname = parent::getParent()->getRootname();
        $template->thumb_dir = $this->config['resource_dir'] . 'img/icons/' . $view . '/';
        
        $template->render();
    }

    // TODO Nette Finder does not support mask for folders
    function getDirectoryContent($actualdir, $mask, $view)
    {
        $thumb_dir = $this->config['resource_dir'] . 'img/icons/' . $view . '/';

        if (!file_exists(WWW_DIR . $thumb_dir)) {
            throw new Exception("Missing folder with icons for " . $view . " view");
            exit;
        }

        $uploadpath = $this->config['uploadpath'];
        $rootname = parent::getParent()->getRootName();
        $uploadroot = $this->config['uploadroot'];

        $absolutePath = parent::getParent()->getAbsolutePath($actualdir);

        $dir_array = array();

        // TODO workaround for missing sort method in Nette Finder
        $sort = new SortedFinder(Finder::find($mask));
        $files = $sort->in($absolutePath)
                    ->exclude(parent::getParent()->thumb . '*')
                    ->orderByType();        

        foreach( $files as $file ) {

                    $name = $file->getFilename();
                    $dir_array[ $name ]['filename'] =  $name;
                    $dir_array[ $name ]['actualdir'] =  $actualdir;
                    $dir_array[ $name ]['modified'] =  $file->getMTime();                    

                    if ( !is_dir($file->getPath() . '/' . $name)  ) {

                            $dir_array[ $name ]['type'] = 'file';
                            $dir_array[ $name ]['size'] =  $file->getSize();
                            $filetype = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                            $dir_array[ $name ]['filetype'] = $filetype;
                            if (file_exists(WWW_DIR . $thumb_dir . $filetype . '.png')) {
                                            $dir_array[ $name ]['icon'] =  $filetype . '.png';
                                            if (($filetype == 'jpg') or ($filetype == 'png') or ($filetype == 'gif') or ($filetype == 'jpeg') or ($filetype == 'bmp')) {
                                                $dir_array[ $name ]['create_thumb'] =  True;
                                            } else
                                                $dir_array[ $name ]['create_thumb'] =  False;
                            } else {
                                            $dir_array[ $name ]['icon'] =  'icon.png';
                                            $dir_array[ $name ]['create_thumb'] =  False;
                            }

                    } else {
                            $dir_array[ $name ]['type'] = 'folder';
                            $dir_array[ $name ]['icon'] =  'folder.png';
                            $dir_array[ $name ]['create_thumb'] =  False;
                    }
        }
        return $dir_array;
    }
}