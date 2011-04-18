<?php

use Nette\Environment;
use Nette\Utils\Finder;

class FMClipboard extends FileManager
{
    /** @var array */
    public $config;

    /** @var string */
    public $actualdir;

    public function __construct()
    {
        parent::__construct();
    }

    public function clearClipboard()
    {
        $namespace = Environment::getSession('file-manager');
        unset($namespace->clipboard);
    }

    public function handleClearClipboard($dir)
    {
        $this->clearClipboard();
        parent::getParent()->handleShowContent($dir);
    }

    public function handlePasteFromClipboard($actualdir)
    {
        $translator = new GettextTranslator(__DIR__ . '/../../locale/FileManager.' . $this->config["lang"] . '.mo');
        $namespace = Environment::getSession('file-manager');

        if ($this['tools']->validPath($actualdir)) {
                    if ($this->config['readonly'] == True)
                                    parent::getParent()->flashMessage(
                                            $translator->translate("File manager is in read-only mode"),
                                            'warning'
                                    );
                    elseif (!isset($namespace->clipboard) || count($namespace->clipboard) <= 0) {
                                    parent::getParent()->flashMessage(
                                            $translator->translate("There is nothing to paste from clipboard!"),
                                            'warning'
                                    );
                    } else {
                                    foreach ($namespace->clipboard as $key => $val) {

                                            if ($val['action'] == 'copy') {

                                                            $this->copy($val['actualdir'], $actualdir, $val['filename']);
                                                            
                                            } elseif ($val['action'] == 'cut') {

                                                            $src_file = parent::getParent()->getAbsolutePath($val['actualdir']) . $val['filename'];
                                                            if (!is_dir($src_file)) // TODO move to handleMoveFile function
                                                                parent::getParent()->handleMoveFile($val['actualdir'], $actualdir, $val['filename']);

                                            }
                                    }

                                    // refresh folder content cache
                                    $this['tools']->clearFromCache(array('fmfiles', $val['actualdir']));
                                    $this['tools']->clearFromCache(array('fmfiles', $actualdir));
                                    $this['tools']->clearFromCache($cache['fmtreeview']);

                                    $this->handleClearClipboard($actualdir);
                    }
        }
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/FMClipboard.latte');

        // set language
        $lang_file = __DIR__ . '/../../locale/FileManager.'. $this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");

        $template->clipboard = Environment::getSession('file-manager')->clipboard;
        $template->actualdir = $this->actualdir;
        $template->rootname = parent::getParent()->getRootname();
        
        $template->render();
    }

    function copy($actualdir, $targetdir, $filename)
    {
        $translator = new GettextTranslator(__DIR__ . '/../../locale/FileManager.' . $this->config["lang"] . '.mo');

        $actualpath = parent::getParent()->getAbsolutePath($actualdir);
        $targetpath = parent::getParent()->getAbsolutePath($targetdir);

        // check name duplicity
        if (file_exists($targetpath . $filename)) {
            $i = 1;
            while (file_exists($targetpath . '(' . $i . ')' . $filename)) {
                $i++;
            }
            $newfilename = '(' . $i . ')' . $filename;
        } else
            $newfilename = $filename;


        if (is_writable($targetpath)) {

            if (!is_dir($actualpath . $filename)) {

                    $filesize = filesize($actualpath . $filename);
                    $disksize = $this['tools']->diskSizeInfo();
                    if ($disksize['spaceleft'] >= $filesize) {

                            copy($actualpath . $filename, $targetpath . $newfilename);
                            parent::getParent()->flashMessage(
                                $translator->translate("File succesfully copied."),
                                'info'
                            );

                    } else
                            parent::getParent()->flashMessage(
                                    $translator->translate("Disk is full!"),
                                    'error'
                            );

            } else {
                    $dirinfo = $this['fmFiles']->getFolderInfo(realpath($actualpath . $filename));
                    $disksize = $this['tools']->diskSizeInfo();
                    if ($disksize['spaceleft'] < $dirinfo['size'])
                                    parent::getParent()->flashMessage(
                                            $translator->translate("Disk is full!"),
                                            'error'
                                    );
                    else {
                                    // detect if target folder is sub-folder of source folder :-)
                                    $ok = True;
                                    foreach (Finder::findDirectories('*')->from(realpath($actualpath . $filename)) as $folder) {
                                        if ($folder->getRealPath() == realpath($targetpath) )
                                                $ok = False;
                                    }

                                    if ($ok == True) {

                                        $this['fmFiles']->recurse_copy($actualpath . $filename, $targetpath . $filename);
                                        parent::getParent()->flashMessage(
                                                $translator->translate("Folder succesfully copied."),
                                                'info'
                                        );
                                    } else
                                        parent::getParent()->flashMessage(
                                                $translator->translate("Parent folder can not be copied to sub-folder!"),
                                                'warning'
                                        );
                    }
            }

        }
    }
}