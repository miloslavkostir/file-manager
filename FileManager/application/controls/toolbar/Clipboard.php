<?php

namespace Ixtrum\FileManager\Application\Controls;

class Clipboard extends \Ixtrum\FileManager\Application\Controls
{

    public function handleClearClipboard()
    {
        $this->system->session->clear("clipboard");
    }

    public function handlePasteFromClipboard()
    {
        if ($this->system->filesystem->validPath($this->getActualDir())) {

            if ($this->system->parameters["readonly"]) {
                $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
            } else {

                foreach ($this->system->session->get("clipboard") as $val) {

                    if ($val["action"] === "copy") {

                        if ($this->system->filesystem->copy($val['actualdir'], $this->getActualDir(), $val['filename'])) {
                            $this->parent->parent->flashMessage($this->system->translator->translate("Succesfully copied - %s", $val['filename']), "info");
                        } else {
                            $this->parent->parent->flashMessage($this->system->translator->translate("An error occured - %s", $val['filename']), "error");
                        }
                    } elseif ($val["action"] === "cut") {

                        if ($this->system->filesystem->move($val["actualdir"], $this->getActualDir(), $val["filename"])) {
                            $this->parent->parent->flashMessage($this->system->translator->translate("Succesfully moved - %s", $val["filename"]), "info");
                        } else {
                            $this->parent->parent->flashMessage($this->system->translator->translate("An error occured - %s", $val["filename"]), "error");
                        }
                    } else {
                        $this->parent->parent->flashMessage($this->system->translator->translate("Unknown action! - %s", $val["action"]), "error");
                    }
                }
                $this->system->session->clear("clipboard");
            }
        } else {
            $this->parent->parent->flashMessage($this->system->translator->translate("Folder %s already does not exist!", $this->getActualDir()), "warning");
        }
    }

    public function handleRemoveFromClipboard($dir, $filename)
    {
        $this->system->session->remove("clipboard", $dir . $filename);
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . "/Clipboard.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->clipboard = $this->system->session->get("clipboard");
        $this->template->rootname = $this->system->filesystem->getRootName();
        $this->template->render();
    }

}