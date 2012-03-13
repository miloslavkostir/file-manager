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
                $template->render();
        }


        protected function  createComponentNewFolderForm()
        {
                $form = new \Nette\Application\UI\Form;
                $form->setTranslator($this->context->translator);
                $form->addText("name", "Name:")
                        ->setRequired("Folder name required.");
                $form->addSubmit("send", "Create");
                $form->onSuccess[] = callback($this, "NewFolderFormSubmitted");

                return $form;
        }


        public function NewFolderFormSubmitted($form)
        {
                $values = $form->values;
                $actualdir = $this->context->system->getActualDir();

                if ($this->context->parameters["readonly"])
                        parent::getParent()->flashMessage("Read-only mode enabled!", "warning");
                else {

                        $tools = $this->context->tools;
                        if ($tools->validPath($actualdir)) {

                                $foldername = $this->context->files->safe_foldername($values->name);
                                if (!$foldername)
                                        parent::getParent()->flashMessage("Folder name can not be used - illegal characters.", "warning");
                                else {

                                        $target_dir = $this->context->tools->getAbsolutePath($actualdir) . $foldername;
                                        if (file_exists($target_dir))
                                                parent::getParent()->flashMessage("This name already exist!", "warning");
                                        else {

                                                if ($this->context->files->mkdir($target_dir)) {

                                                        if ($this->context->parameters["cache"]) {

                                                                $caching = $this->context->caching;
                                                                $caching->deleteItem(array("content", $tools->getRealPath($tools->getAbsolutePath($actualdir))));
                                                                $caching->deleteItem(NULL, array("tags" => "treeview"));
                                                        }

                                                        parent::getParent()->flashMessage("Folder successfully created", "info");
                                                        parent::getParent()->handleShowContent($actualdir);
                                                } else
                                                        parent::getParent()->flashMessage("An unkonwn error occurred during folder creation", "info");
                                        }
                                }
                        } else
                                parent::getParent()->flashMessage("Folder $actualdir already does not exist!", "warning");
                }

                parent::getParent()->handleShowContent($actualdir);
        }
}