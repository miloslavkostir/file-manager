<?php

namespace Ixtrum\FileManager\Application\Controls;

use Nette\Application\UI\Form;

class Rename extends \Ixtrum\FileManager\Application\Plugins
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
        $path = $this->system->filesystem->getAbsolutePath($this->getActualDir());

        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
        } elseif ($form->values->new_filename == $form->values->orig_filename) {
            $this->parent->parent->flashMessage($this->system->translator->translate("New name can not be the same!"), "warning");
        } elseif (file_exists($path . $form->values->new_filename)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("The name %s was already used.", $form->values->new_filename), "warning");
        } elseif (!file_exists($path . $form->values->orig_filename)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("File/folder %s does not already exists!", $form->values->orig_filename), "error");
        } else {

            $origPath = $path . $form->values->orig_filename;
            if (is_dir(realpath($origPath))) {

                $new_filename = $this->system->filesystem->safeFoldername($form->values->new_filename);
                $this->system->thumbs->deleteDirThumbs($origPath);

                if ($this->system->parameters["cache"]) {

                    $this->system->caching->deleteItem(array(
                        "content",
                        realpath($path)
                    ));
                    $this->system->caching->deleteItemsRecursive($origPath);
                }
            } else {

                $new_filename = $this->system->filesystem->safeFilename($form->values->new_filename);
                $this->system->thumbs->deleteThumb(realpath($origPath));

                if ($this->system->parameters["cache"]) {

                    $this->system->caching->deleteItem(array(
                        "content",
                        realpath($path)
                    ));
                }
            }

            if (rename($origPath, $path . $new_filename)) {

                $this->parent->parent->flashMessage($this->system->translator->translate("Successfully renamed to %s.", $new_filename), "info");
                $this->system->session->clear("clipboard");
            } else {
                $this->parent->parent->flashMessage($this->system->translator->translate("An error occurred during %s renaming!", $form->values->orig_filename), "error");
            }
        }
    }

}