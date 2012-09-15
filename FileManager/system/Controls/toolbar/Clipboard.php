<?php

namespace Ixtrum\FileManager\Controls;

class Clipboard extends \Ixtrum\FileManager
{

    public function handleClearClipboard()
    {
        $this->context->session->clear("clipboard");
    }

    public function handlePasteFromClipboard()
    {
        $actualdir = $this->context->session->get("actualdir");
        if ($this->context->filesystem->validPath($actualdir)) {

            if ($this->context->parameters["readonly"]) {
                $this->parent->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
            } else {

                foreach ($this->context->session->get("clipboard") as $val) {

                    if ($val["action"] === "copy") {

                        if ($this->context->filesystem->copy($val['actualdir'], $actualdir, $val['filename'])) {
                            $this->parent->parent->flashMessage($this->context->translator->translate("Succesfully copied - %s", $val['filename']), "info");
                        } else {
                            $this->parent->parent->flashMessage($this->context->translator->translate("An error occured - %s", $val['filename']), "error");
                        }
                    } elseif ($val["action"] === "cut") {

                        if ($this->context->filesystem->move($val["actualdir"], $actualdir, $val["filename"])) {
                            $this->parent->parent->flashMessage($this->context->translator->translate("Succesfully moved - %s", $val["filename"]), "info");
                        } else {
                            $this->parent->parent->flashMessage($this->context->translator->translate("An error occured - %s", $val["filename"]), "error");
                        }
                    } else {
                        $this->parent->parent->flashMessage($this->context->translator->translate("Unknown action! - %s", $val["action"]), "error");
                    }
                }
                $this->context->session->clear("clipboard");
            }
        } else {
            $this->parent->parent->flashMessage($this->context->translator->translate("Folder %s already does not exist!", $actualdir), "warning");
        }
    }

    public function handleRemoveFromClipboard($actualdir, $filename)
    {
        $this->context->session->remove("clipboard", $actualdir . $filename);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . "/Clipboard.latte");
        $template->setTranslator($this->context->translator);
        $template->clipboard = $this->context->session->get("clipboard");
        $template->rootname = $this->context->filesystem->getRootName();
        $template->render();
    }

}