<?php

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
        $namespace = $this->presenter->context->session->getNamespace('file-manager');
        unset($namespace->clipboard);
    }

    public function handleClearClipboard()
    {
        $namespace = $this->presenter->context->session->getNamespace('file-manager');        
        $this->clearClipboard();
        parent::getParent()->handleShowContent($namespace->actualdir);
    }

    public function handlePasteFromClipboard()
    {
        $translator =  parent::getParent()->getTranslator();
        $namespace = $this->presenter->context->session->getNamespace('file-manager');
        $actualdir = $this['system']->getActualDir();
        
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

                                    $this->handleClearClipboard();
                    }
        }
    }

    public function handleRemoveFromClipboard($actualdir, $filename)
    {
        $translator = parent::getParent()->getTranslator();
        $namespace = $this->presenter->context->session->getNamespace('file-manager');
        $path = $actualdir.$filename;

        if (isset($namespace->clipboard[$path]))
            unset($namespace->clipboard[$path]);
        else
            parent::getParent()->flashMessage(
                    $translator->translate('Item %s does not exist in clipboard!', $path),
                    'error'
            );

        parent::getParent()->handleShowContent($namespace->actualdir);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/Clipboard.latte');
        $template->setTranslator(parent::getParent()->getTranslator());

        $namespace = $this->presenter->context->session->getNamespace('file-manager');
        $template->clipboard = $namespace->clipboard;
        $template->rootname = parent::getParent()->getRootname();
        
        $template->render();
    }
}