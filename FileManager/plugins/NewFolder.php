<?php

namespace Ixtrum\FileManager\Plugins;

class NewFolder extends \Ixtrum\FileManager
{

    /** @var string */
    public $title = "New folder";

    /** @var bool */
    public $toolbarPlugin = true;

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . "/NewFolder.latte");
        $template->setTranslator($this->system->translator);
        $template->render();
    }

    protected function createComponentNewFolderForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->setTranslator($this->system->translator);
        $form->addText("name", "Name:")
            ->setRequired("Folder name required.");
        $form->addSubmit("send", "Create");
        $form->onSuccess[] = $this->newFolderFormSubmitted;
        $form->onError[] = $this->parent->parent->onFormError;
        return $form;
    }

    public function newFolderFormSubmitted($form)
    {
        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            if ($this->system->filesystem->validPath($this->getActualDir())) {

                $foldername = $this->system->filesystem->safeFoldername($form->values->name);
                if (!$foldername) {
                    $this->parent->parent->flashMessage($this->system->translator->translate("Folder name '%s' can not be used - not allowed characters used.", $form->values->name), "warning");
                } else {

                    $target_dir = $this->system->filesystem->getAbsolutePath($this->getActualDir()) . $foldername;
                    if (is_dir($target_dir)) {
                        $this->parent->parent->flashMessage($this->system->translator->translate("Target name %s already exists!", $foldername), "warning");
                    } else {

                        if ($this->system->filesystem->mkdir($target_dir)) {

                            if ($this->system->parameters["cache"]) {

                                $this->system->caching->deleteItem(array(
                                    "content",
                                    $this->system->filesystem->getRealPath($this->system->filesystem->getAbsolutePath($this->getActualDir()))
                                ));
                                $this->system->caching->deleteItem(NULL, array("tags" => "treeview"));
                            }

                            $this->parent->parent->flashMessage($this->system->translator->translate("Folder %s successfully created", $foldername), "info");
                        } else {
                            $this->parent->parent->flashMessage($this->system->translator->translate("An unkonwn error occurred during folder %s creation", $foldername), "info");
                        }
                    }
                }
            } else {
                $this->parent->parent->flashMessage($this->system->translator->translate("Folder %s already does not exist!", $this->getActualDir()), "warning");
            }
        }
    }

}