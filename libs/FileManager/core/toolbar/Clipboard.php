<?php

use Nette\Environment;
use Nette\Utils\Finder;

class Clipboard extends FileManager
{
    /** @var array */
    public $config;

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

                                                            if ($this['files']->copy($val['actualdir'], $actualdir, $val['filename']))
                                                                    parent::getParent()->flashMessage(
                                                                            $translator->translate("Succesfully copied."),
                                                                            'info'
                                                                    );
                                                            else
                                                                    parent::getParent()->flashMessage(
                                                                            $translator->translate("An error occured!"),
                                                                            'error'
                                                                    );                                                                
                                                            
                                            } elseif ($val['action'] == 'cut') {

                                                            if ($this['files']->move($val['actualdir'], $actualdir, $val['filename']))
                                                                    parent::getParent()->flashMessage(
                                                                            $translator->translate("Succesfully moved."),
                                                                            'info'
                                                                    );
                                                            else
                                                                    parent::getParent()->flashMessage(
                                                                            $translator->translate("An error occured!"),
                                                                            'error'
                                                                    );                                                                    

                                            } else
                                                            parent::getParent()->flashMessage(
                                                                    $translator->translate("Unknown action!"),
                                                                    'error'
                                                            );                                                
                                    }

                                    // refresh folder content cache
                                    $this['tools']->clearFromCache(array('fmfiles', $val['actualdir']));
                                    $this['tools']->clearFromCache(array('fmfiles', $actualdir));
                                    $this['tools']->clearFromCache('fmtreeview');

                                    $this->handleClearClipboard($actualdir);
                    }
        }
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/Clipboard.latte');

        // set language
        $lang_file = __DIR__ . '/../../locale/FileManager.'. $this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");

        $namespace = Environment::getSession('file-manager');
        $template->clipboard = $namespace->clipboard;
        $template->actualdir = $namespace->actualdir;
        $template->rootname = parent::getParent()->getRootname();
        
        $template->render();
    }
}