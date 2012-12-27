<?php

namespace Ixtrum\FileManager\Application\Controls;

use Nette\Application\UI\Form;

class Rename extends \Ixtrum\FileManager\Application\Controls
{

    public function render()
    {
        $file = "";
        if (isset($this->selectedFiles[0])) {
            $file = $this->selectedFiles[0];
        }
        $this->template->setFile(__DIR__ . "/Rename.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->origFile = $file;

        $this->getComponent("renameForm")->setDefaults(array(
            "new_filename" => $file,
            "orig_filename" => $file,
        ));

        $this->template->render();
    }

    protected function createComponentRenameForm()
    {
        $form = new Form;
        $form->setTranslator($this->system->translator);
        $form->addText("new_filename", "New name")
                ->setRequired("New name required.");
        $form->addHidden("orig_filename");
        $form->addSubmit("send", "OK");
        $form->onSuccess[] = $this->renameFormSuccess;
        $form->onError[] = $this->parent->parent->onFormError;
        return $form;
    }

    public function renameFormSuccess(Form $form)
    {
        $path = $this->getAbsolutePath($this->getActualDir());

        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
            return;
        }

        if ($form->values->new_filename === $form->values->orig_filename) {
            $this->parent->parent->flashMessage($this->system->translator->translate("New name can not be the same!"), "warning");
            return;
        }

        if (file_exists($path . DIRECTORY_SEPARATOR . $form->values->new_filename)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("The name %s was already used.", $form->values->new_filename), "warning");
            return;
        }

        if (!file_exists($path . $form->values->orig_filename)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("File/folder %s does not already exists!", $form->values->orig_filename), "error");
            return;
        }

        $origPath = $path . DIRECTORY_SEPARATOR . $form->values->orig_filename;
        if (is_dir($origPath)) {

            $newFilename = $this->system->filesystem->safeFoldername($form->values->new_filename);
            $this->system->thumbs->deleteDirThumbs($origPath);

            if ($this->system->parameters["cache"]) {

                $this->system->caching->deleteItem(array("content", $path));
                $this->system->caching->deleteItemsRecursive($origPath);
            }
        } else {

            $newFilename = $this->system->filesystem->safeFilename($form->values->new_filename);
            $this->system->thumbs->deleteThumb($origPath);

            if ($this->system->parameters["cache"]) {
                $this->system->caching->deleteItem(array("content", $path));
            }
        }

        if (rename($origPath, $path . DIRECTORY_SEPARATOR . $newFilename)) {

            $this->parent->parent->flashMessage($this->system->translator->translate("Successfully renamed to %s.", $newFilename), "info");
            $this->system->session->clear("clipboard");
        } else {
            $this->parent->parent->flashMessage($this->system->translator->translate("An error occurred during %s renaming!", $form->values->orig_filename), "error");
        }
    }

}