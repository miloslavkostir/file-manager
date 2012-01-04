<?php

namespace Netfileman;

use Nette\Application\Responses\FileResponse;
use Nette\Image;
use Nette\Utils\Finder;
use Nette\Templating\DefaultHelpers;

class Content extends FileManager
{
    /** @var array */
    public $config;

    public function __construct()
    {
        parent::__construct();
    }

    public function handleShowFileInfo($filename = "")
    {
        // if sended by AJAX
        if (!$filename)
            $filename = $this->presenter->context->httpRequest->getQuery('filename');

        parent::getParent()->handleShowFileInfo($filename);
    }

    public function handleShowMultiFileInfo($files = "")
    {
        $actualdir = $this['system']->getActualDir();
        $translator = $this['system']->getTranslator();

        // if sended by AJAX
        if (!$files)
            $files = $this->presenter->context->httpRequest->getPost('files');

        if (is_array($files)) {
                $info = $this['files']->getFilesInfo($actualdir, $files, true);
                $this->presenter->payload->result = 'success';
                $this->presenter->payload->size = DefaultHelpers::bytes($info['size']);
                $this->presenter->payload->dirCount = $info['dirCount'];
                $this->presenter->payload->fileCount = $info['fileCount'];
                $this->presenter->sendPayload();
        } else
                parent::getParent()->flashMessage(
                        $translator->translate("Incorrect input type data. Must be an array!"),
                        'error'
                );
    }

    public function handleCopyToClipboard($filename = "")
    {
        // if sended by AJAX
        if (!$filename)
            $filename = $this->presenter->context->httpRequest->getPost('filename');

        $session = $this->presenter->context->session->getSection('file-manager');
        $actualdir = $this['system']->getActualDir();

        if ($this['tools']->validPath($actualdir, $filename)) {
            $session->clipboard[$actualdir.$filename] = array(
                'action' => 'copy',
                'actualdir' => $actualdir,
                'filename' => $filename
            );

            $this->handleShowContent($actualdir);
        }
    }

    public function handleMultiCopyToClipboard($files = "")
    {
        $session = $this->presenter->context->session->getSection('file-manager');
        $actualdir = $this['system']->getActualDir();
        $translator = $this['system']->getTranslator();

        // if sended by AJAX
        if (!$files)
            $files = $this->presenter->context->httpRequest->getPost('files');

        if (is_array($files)) {

                foreach($files as $file) {
                        $session->clipboard[$actualdir.$file] = array(
                            'action' => 'copy',
                            'actualdir' => $actualdir,
                            'filename' => $file
                        );
                }
        } else
                parent::getParent()->flashMessage(
                        $translator->translate("Incorrect input type data. Must be an array!"),
                        'error'
                );

        parent::getParent()->refreshSnippets(array('clipboard'));
    }

    public function handleCutToClipboard($filename = "")
    {
        // if sended by AJAX
        if (!$filename)
            $filename = $this->presenter->context->httpRequest->getPost('filename');

        $session = $this->presenter->context->session->getSection('file-manager');
        $actualdir = $this['system']->getActualDir();

        if ($this['tools']->validPath($actualdir, $filename)) {
            $session->clipboard[$actualdir.$filename] = array(
                'action' => 'cut',
                'actualdir' => $actualdir,
                'filename' => $filename
            );

            $this->handleShowContent($actualdir);
        }
    }

    public function handleMultiCutToClipboard($files = "")
    {
        $session = $this->presenter->context->session->getSection('file-manager');
        $actualdir = $this['system']->getActualDir();
        $translator = $this['system']->getTranslator();

        // if sended by AJAX
        if (!$files)
            $files = $this->presenter->context->httpRequest->getPost('files');

        if (is_array($files)) {

                foreach($files as $file) {
                        $session->clipboard[$actualdir.$file] = array(
                            'action' => 'cut',
                            'actualdir' => $actualdir,
                            'filename' => $file
                        );
                }
        } else
                parent::getParent()->flashMessage(
                        $translator->translate("Incorrect input type data. Must be an array!"),
                        'error'
                );

        parent::getParent()->refreshSnippets(array('clipboard'));
    }

    public function handleDelete($filename = "")
    {
        $actualdir = $this['system']->getActualDir();
        $translator = $this['system']->getTranslator();

        // if sended by AJAX
        if (!$filename)
            $filename = $this->presenter->context->httpRequest->getQuery('filename');

        if ($this->config['readonly'] == True)
                        parent::getParent()->flashMessage(
                                $translator->translate("File manager is in read-only mode"),
                                'warning'
                        );
        elseif ($this['tools']->validPath($actualdir, $filename)) {

                        if ($this['files']->delete($actualdir, $filename))
                            parent::getParent()->flashMessage(
                                    $translator->translate('Successfuly deleted'),
                                    'info'
                            );
                        else
                            parent::getParent()->flashMessage(
                                    $translator->translate('An error occured!'),
                                    'error'
                            );

        }

        $this->handleShowContent($actualdir);
    }

    public function handleMultiDelete($files = "")
    {
        $actualdir = $this['system']->getActualDir();
        $translator = $this['system']->getTranslator();

        // if sended by AJAX
        if (!$files)
            $files = $this->presenter->context->httpRequest->getPost('files');

        if ($this->config['readonly'] == True)
                        parent::getParent()->flashMessage(
                                $translator->translate("File manager is in read-only mode"),
                                'warning'
                        );
        else {
                        if (is_array($files)) {
                                foreach($files as $file) {
                                            if ($this['files']->delete($actualdir, $file))
                                                parent::getParent()->flashMessage(
                                                        $translator->translate('Successfuly deleted'),
                                                        'info'
                                                );
                                            else
                                                parent::getParent()->flashMessage(
                                                        $translator->translate('An error occured!'),
                                                        'error'
                                                );
                                }
                        } else
                                parent::getParent()->flashMessage(
                                        $translator->translate("Incorrect input type data. Must be an array!"),
                                        'error'
                                );

                        $this->handleShowContent($actualdir);
        }
    }

    public function handleMultiDownload($files = "")
    {
        $actualdir = $this['system']->getActualDir();

        // if sended by AJAX
        if (!$files)
            $files = $this->presenter->context->httpRequest->getPost('files');

        $path = $this['zip']->addFiles($actualdir, $files);

        $payload = $this->presenter->payload;
        $payload->result = 'success';
        $payload->filename = $path;

        parent::getParent()->refreshSnippets(array('message'));
    }

    public function handleOrderBy($key)
    {
        $session = $this->presenter->context->session->getSection('file-manager');
        $session->order = $key;
        $actualdir = $this['system']->getActualDir();
        $absPath = $this['tools']->getRealPath($this['tools']->getAbsolutePath($actualdir));

        if ($this->config['cache'] == True)
            $this['caching']->deleteItem(array('content', $absPath));

        parent::getParent()->handleShowContent($actualdir);
    }

    public function handleRunPlugin($plugin, $files = "")
    {
        // if sended by AJAX
        if (!$files)
            $files = $this->presenter->context->httpRequest->getPost('files');

        parent::getParent()->handleRunPlugin($plugin, $files);
    }

    public function handleDownloadFile($filename = "")
    {
        $actualdir = $this['system']->getActualDir();

        // if sended by AJAX
        if (!$filename)
            $filename = $this->presenter->context->httpRequest->getQuery('filename');

        if ($this['tools']->validPath($actualdir, $filename)) {
            $path = $this['tools']->getAbsolutePath($actualdir) . $filename;
            $this->presenter->sendResponse(new FileResponse($path, NULL, NULL));
        }
    }

    public function handleDownloadZip($filename = "")
    {
        // if sended by AJAX
        if (!$filename)
            $filename = $this->presenter->context->httpRequest->getQuery('filename');

        $path = $this['zip']->getTempDir() . '/' . $filename;

        if (file_exists($path))
            $this->presenter->sendResponse(new FileResponse($path, NULL, NULL));
    }

    public function handleGoToParent()
    {
        $actualdir = $this['system']->getActualDir();
        $parent = dirname($actualdir);

        if ($parent == '\\' || $parent == '.')
            $parent_path = $this->getRootname();
        else
            $parent_path = $parent . '/';

        $this->handleShowContent($parent_path);
    }

    public function handleMove($targetdir = "", $filename = "")
    {
        $session = $this->presenter->context->session->getSection('file-manager');
        $actualdir = $session->actualdir;
        $translator = $this['system']->getTranslator();
        $request = $this->presenter->context->httpRequest;

        // if sended by AJAX
        if (!$targetdir)
            $targetdir = $request->getQuery('targetdir');
        if (!$filename)
            $filename = $request->getQuery('filename');

        if ($this->config['readonly'] == True)
                        parent::getParent()->flashMessage(
                                $translator->translate("File manager is in read-only mode!"),
                                'warning'
                        );
        else {

                if ($this['files']->move($actualdir, $targetdir, $filename)) {
                        $this->presenter->payload->result = 'success';
                        parent::getParent()->flashMessage(
                                $translator->translate('Successfuly moved.'),
                                'info'
                        );
                        parent::getParent()->handleShowContent($targetdir);
                } else {
                        parent::getParent()->flashMessage(
                                $translator->translate('An error occured. File was not moved.'),
                                'error'
                        );
                        parent::getParent()->handleShowContent($actualdir);
                }
        }
    }

    public function handleShowContent($actualdir) {
        parent::getParent()->handleShowContent($actualdir);
    }

    public function handleShowThumb($dir, $file)
    {
        $path = $this['tools']->getAbsolutePath($dir) . $file;

        $cache_file =  $this['files']->createThumbName($dir, $file);

        if ( file_exists($cache_file['path']) ) {
                    $image = Image::fromFile($cache_file['path']);
                    $image->send();
        } else {
                    $disksize = $this['tools']->diskSizeInfo();
                    if ($disksize['spaceleft'] > 2 ) {

                            $status = true;
                            if (function_exists('exec'))
                                    exec('convert -version', $results, $status);

                            if (class_exists('\Nette\ImageMagick') && !$status) {
                                    $image = new \Nette\ImageMagick($path);
                            } elseif (class_exists('Imagick')) {
                                    $thumb = new \Imagick($path);
                                    $thumb->resizeImage(90, 0, \Imagick::FILTER_LANCZOS, 1);
                                    $thumb->writeImage($cache_file['path']);
                                    $thumb->destroy();
                                    $image = Image::fromFile($path);
                            } else
                                    $image = Image::fromFile($path);

                            $image->resize(96, NULL);
                            $image->save($cache_file['path'], 80);
                            $image->send();
                    }
        }
    }

    public function render()
    {
        $translator = $this['system']->getTranslator();
        $session = $this->presenter->context->session->getSection('file-manager');
        $actualdir = $session->actualdir;

        $template = $this->template;
        $template->setTranslator($translator);

        $view = $session->view;
        $mask = $session->mask;
        $order = $session->order;

        $plugins = parent::getParent()->plugins;

        if (!$mask)
            $mask = '*';

        if (!$order)
            $order = 'type';

        if ($view) {
            $c_template = __DIR__ . '/' . $view . '.latte';
            if (file_exists($c_template))
                $template->setFile($c_template);
            else {
                $template->setFile(__DIR__ . '/large.latte');
                parent::getParent()->flashMessage(
                            $translator->translate('Unknown view selected.'),
                            'warning'
                        );
                $view = 'large';
            }
        } else {
                $template->setFile(__DIR__ . '/large.latte');
                $view = 'large';
        }

        $template->files = $this->loadData($actualdir, $mask, $view, $order);
        $template->actualdir = $actualdir;
        $template->rootname = $this['tools']->getRootName();
        $template->thumb_dir = $this->config['resource_dir'] . 'img/icons/' . $view . '/';

        if ($plugins) {
            $cotnextPlugins = array();

            foreach($plugins as $plugin) {
                if ($plugin['context'] == True)
                    $contextPlugins[] = $plugin;
            }

            if ($contextPlugins)
                $template->plugins = $contextPlugins;
        }

        $template->render();
    }

    /**
     * Load directory content
     * TODO Nette Finder does not support mask for folders
     *
     * @param string $actualdir
     * @param string $mask
     * @param string $view
     * @param string $order
     * @return array
     */
    function getDirectoryContent($actualdir, $mask, $view, $order)
    {
        $thumb_dir = $this->config['resource_dir'] . 'img/icons/' . $view . '/';

        if (!file_exists(WWW_DIR . $thumb_dir)) {
            throw new \Exception("Missing folder with icons for " . $view . " view");
            exit;
        }

        $uploadpath = $this->config['uploadpath'];
        $rootname = $this['tools']->getRootName();
        $uploadroot = $this->config['uploadroot'];

        $absolutePath = $this['tools']->getAbsolutePath($actualdir);

        $dir_array = array();

        $files = SortedFinder::find($mask)
                    ->in($absolutePath)
                    ->exclude(parent::getParent()->thumb . '*')
                    ->orderBy($order);

        foreach( $files as $file ) {

                    $name = $file->getFilename();
                    $dir_array[ $name ]['filename'] =  $name;
                    $dir_array[ $name ]['actualdir'] =  $actualdir;
                    $dir_array[ $name ]['modified'] =  $file->getMTime();

                    if ( !is_dir($file->getPath() . '/' . $name)  ) {

                            $dir_array[ $name ]['type'] = 'file';
                            $dir_array[ $name ]['size'] =  $this['files']->getFileSize($file->getPathName());
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

    /**
     * Load data
     *
     * @param string $actualdir
     * @param string $mask
     * @param string $view
     * @param string $order
     * @return array
     */
    public function loadData($actualdir, $mask, $view, $order)
    {
        if ($this->config['cache'] == True) {

            $absDir = $this['tools']->getRealPath($this['tools']->getAbsolutePath($actualdir));
            $cacheData = $this['caching']->getItem(array('content',  $absDir));

            if (!$cacheData) {
                $output = $this->getDirectoryContent($actualdir, $mask, $view, $order);
                $this['caching']->saveItem(array('content',  $absDir), $output);
                return $output;
            } else
                return $cacheData;

        } else
            return $this->getDirectoryContent($actualdir, $mask, $view, $order);
    }
}