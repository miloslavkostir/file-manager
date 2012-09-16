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
        if ($this->context->filesystem->validPath($this->actualDir)) {

            if ($this->context->parameters["readonly"]) {
                $this->parent->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
            } else {

                foreach ($this->context->session->get("clipboard") as $val) {

                    if ($val["action"] === "copy") {

                        if ($this->context->filesystem->copy($val['actualdir'], $this->actualDir, $val['filename'])) {
                            $this->parent->parent->flashMessage($this->context->translator->translate("Succesfully copied - %s", $val['filename']), "info");
                        } else {
                            $this->parent->parent->flashMessage($this->context->translator->translate("An error occured - %s", $val['filename']), "error");
                        }
                    } elseif ($val["action"] === "cut") {

                        if ($this->context->filesystem->move($val["actualdir"], $this->actualDir, $val["filename"])) {
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
            $this->parent->parent->flashMessage($this->context->translator->translate("Folder %s already does not exist!", $this->actualDir), "warning");
        }
    }

    public function handleRemoveFromClipboard($dir, $filename)
    {
        $this->context->session->remove("clipboard", $dir . $filename);
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . "/Clipboard.latte");
        $this->template->setTranslator($this->context->translator);
        $this->template->clipboard = $this->context->session->get("clipboard");
        $this->template->rootname = $this->context->filesystem->getRootName();
        $this->template->render();
    }

}