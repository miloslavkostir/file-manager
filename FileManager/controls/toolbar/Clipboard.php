<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Utils\Finder;

class Clipboard extends \Ixtrum\FileManager
{

    public function __construct($userConfig)
    {
        parent::__construct($userConfig);
    }

    public function handleClearClipboard()
    {
        $session = $this->presenter->context->session->getSection('file-manager');
        $this->context->application->clearClipboard();
        parent::getParent()->handleShowContent($session->actualdir);
    }

    public function handlePasteFromClipboard()
    {
        $session = $this->presenter->context->session->getSection('file-manager');
        $actualdir = $this->context->application->getActualDir();

        $translator = $this->context->translator;
        if ($this->context->tools->validPath($actualdir)) {

            if ($this->context->parameters["readonly"]) {
                parent::getParent()->flashMessage($translator->translate("Read-only mode enabled!"), "warning");
            } elseif (!isset($session->clipboard) || count($session->clipboard) <= 0) {
                parent::getParent()->flashMessage($translator->translate("There is nothing to paste from clipboard!"), "warning");
            } else {

                foreach ($session->clipboard as $key => $val) {

                    if ($val["action"] === "copy") {

                        if ($this->context->filesystem->copy($val['actualdir'], $actualdir, $val['filename'])) {
                            parent::getParent()->flashMessage($translator->translate("Succesfully copied - %s", $val['filename']), "info");
                        } else {
                            parent::getParent()->flashMessage($translator->translate("An error occured - %s", $val['filename']), "error");
                        }
                    } elseif ($val["action"] === "cut") {

                        if ($this->context->filesystem->move($val["actualdir"], $actualdir, $val["filename"])) {
                            parent::getParent()->flashMessage($translator->translate("Succesfully moved - %s", $val["filename"]), "info");
                        } else {
                            parent::getParent()->flashMessage($translator->translate("An error occured - %s", $val["filename"]), "error");
                        }
                    } else {
                        parent::getParent()->flashMessage($translator->translate("Unknown action! - %s", $val["action"]), "error");
                    }
                }

                $this->handleClearClipboard();
            }
        } else {
            parent::getParent()->flashMessage($translator->translate("Folder %s already does not exist!", $actualdir), "warning");
        }
    }

    public function handleRemoveFromClipboard($actualdir, $filename)
    {
        $session = $this->presenter->context->session->getSection("file-manager");
        $path = $actualdir . $filename;

        if (isset($session->clipboard[$path])) {
            unset($session->clipboard[$path]);
        } else {
            parent::getParent()->flashMessage($this->context->translator->translate("Item %s does not exist in clipboard!", $path), "error");
        }

        parent::getParent()->handleShowContent($session->actualdir);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . "/Clipboard.latte");
        $template->setTranslator($this->context->translator);

        $session = $this->presenter->context->session->getSection("file-manager");
        $template->clipboard = $session->clipboard;
        $template->rootname = $this->context->tools->getRootName();

        $template->render();
    }

}