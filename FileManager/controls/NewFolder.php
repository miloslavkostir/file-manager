<?php

namespace Ixtrum\FileManager\Controls;

class NewFolder extends \Ixtrum\FileManager
{

    public function __construct($userConfig)
    {
        parent::__construct($userConfig);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . "/NewFolder.latte");
        $template->setTranslator($this->context->translator);
        $template->render();
    }

    protected function createComponentNewFolderForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->setTranslator($this->context->translator);
        $form->addText("name", "Name:")
                ->setRequired("Folder name required.");
        $form->addSubmit("send", "Create");
        $form->onSuccess[] = $this->newFolderFormSubmitted;
        $form->onError[] = $this->parent->onFormError;
        return $form;
    }

    public function NewFolderFormSubmitted($form)
    {
        $values = $form->values;
        $actualdir = $this->context->application->getActualDir();

        if ($this->context->parameters["readonly"]) {
            $this->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            $filesystem = $this->context->filesystem;
            if ($filesystem->validPath($actualdir)) {

                $foldername = $this->context->filesystem->safeFoldername($values->name);
                if (!$foldername) {
                    $this->parent->flashMessage($this->context->translator->translate("Folder name '%s' can not be used - not allowed characters used.", $values->name), "warning");
                } else {

                    $target_dir = $this->context->filesystem->getAbsolutePath($actualdir) . $foldername;
                    if (is_dir($target_dir)) {
                        $this->parent->flashMessage($this->context->translator->translate("Target name %s already exists!", $foldername), "warning");
                    } else {

                        if ($this->context->filesystem->mkdir($target_dir)) {

                            if ($this->context->parameters["cache"]) {

                                $caching = $this->context->caching;
                                $caching->deleteItem(array("content", $filesystem->getRealPath($filesystem->getAbsolutePath($actualdir))));
                                $caching->deleteItem(NULL, array("tags" => "treeview"));
                            }

                            $this->parent->flashMessage($this->context->translator->translate("Folder %s successfully created", $foldername), "info");
                            $this->parent->handleShowContent($actualdir);
                        } else {
                            $this->parent->flashMessage($this->context->translator->translate("An unkonwn error occurred during folder %s creation", $foldername), "info");
                        }
                    }
                }
            } else {
                $this->parent->flashMessage($this->context->translator->translate("Folder %s already does not exist!", $actualdir), "warning");
            }
        }

        $this->parent->handleShowContent($actualdir);
    }

}