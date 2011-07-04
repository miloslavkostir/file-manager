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
        $session = $this->presenter->context->session->getSection('file-manager');
        unset($session->clipboard);
    }

    public function handleClearClipboard()
    {
        $session = $this->presenter->context->session->getSection('file-manager');
        $this->clearClipboard();
        parent::getParent()->handleShowContent($session->actualdir);
    }

    public function handlePasteFromClipboard()
    {
        $translator =  $this['system']->getTranslator();
        $session = $this->presenter->context->session->getSection('file-manager');
        $actualdir = $this['system']->getActualDir();

        if ($this['tools']->validPath($actualdir)) {
                    if ($this->config['readonly'] == True)
                                    parent::getParent()->flashMessage(
                                            $translator->translate("File manager is in read-only mode"),
                                            'warning'
                                    );
                    elseif (!isset($session->clipboard) || count($session->clipboard) <= 0) {
                                    parent::getParent()->flashMessage(
                                            $translator->translate("There is nothing to paste from clipboard!"),
                                            'warning'
                                    );
                    } else {
                                    foreach ($session->clipboard as $key => $val) {

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
        $translator = $this['system']->getTranslator();
        $session = $this->presenter->context->session->getSection('file-manager');
        $path = $actualdir.$filename;

        if (isset($session->clipboard[$path]))
            unset($session->clipboard[$path]);
        else
            parent::getParent()->flashMessage(
                    $translator->translate('Item %s does not exist in clipboard!', $path),
                    'error'
            );

        parent::getParent()->handleShowContent($session->actualdir);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/Clipboard.latte');
        $template->setTranslator($this['system']->getTranslator());

        $session = $this->presenter->context->session->getSection('file-manager');
        $template->clipboard = $session->clipboard;
        $template->rootname = $this['tools']->getRootName();

        $template->render();
    }
}