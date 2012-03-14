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
            $this->context->system->clearClipboard();
            parent::getParent()->handleShowContent($session->actualdir);
        }


        public function handlePasteFromClipboard()
        {
                $session = $this->presenter->context->session->getSection('file-manager');
                $actualdir = $this->context->system->getActualDir();

                if ($this->context->tools->validPath($actualdir)) {

                        if ($this->context->parameters["readonly"])
                                parent::getParent()->flashMessage("Read-only mode enabled!", "warning");
                        elseif (!isset($session->clipboard) || count($session->clipboard) <= 0)
                                parent::getParent()->flashMessage("There is nothing to paste from clipboard!", "warning");
                        else {

                                foreach ($session->clipboard as $key => $val) {

                                        if ($val["action"] === "copy") {

                                                if ($this->context->files->copy($val['actualdir'], $actualdir, $val['filename']))
                                                        parent::getParent()->flashMessage("Succesfully copied.", "info");
                                                else
                                                        parent::getParent()->flashMessage("An error occured!", "error");

                                        } elseif ($val["action"] === "cut") {

                                                if ($this->context->files->move($val["actualdir"], $actualdir, $val["filename"]))
                                                        parent::getParent()->flashMessage("Succesfully moved.", "info");
                                                else
                                                        parent::getParent()->flashMessage("An error occured!", "error");

                                        } else
                                                parent::getParent()->flashMessage("Unknown action!", "error");
                                }

                                $this->handleClearClipboard();
                        }
                } else
                        parent::getParent()->flashMessage("Dir $actualdir already does not exist!", "warning");
        }


        public function handleRemoveFromClipboard($actualdir, $filename)
        {
                $session = $this->presenter->context->session->getSection("file-manager");
                $path = $actualdir.$filename;

                if (isset($session->clipboard[$path]))
                        unset($session->clipboard[$path]);
                else
                        parent::getParent()->flashMessage("Item $path does not exist in clipboard!", "error");

                parent::getParent()->handleShowContent($session->actualdir);
        }


        public function render()
        {
                $template = $this->template;
                $template->setFile(__DIR__ . "/Clipboard.latte");

                $session = $this->presenter->context->session->getSection("file-manager");
                $template->clipboard = $session->clipboard;
                $template->rootname = $this->context->tools->getRootName();

                $template->render();
        }
}