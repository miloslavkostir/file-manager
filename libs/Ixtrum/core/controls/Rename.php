<?php

namespace Ixtrum;

use Nette\Application\UI\Form;

class Rename extends FileManager
{
        /** @var string */
        public $files;


        public function __construct($userConfig)
        {
                parent::__construct($userConfig);
        }


        public function render()
        {
                $template = $this->template;
                $template->setFile(__DIR__ . "/Rename.latte");
                $template->setTranslator($this->context->translator);
                $template->origFile = $this->files;

                $this["renameForm"]->setDefaults(array(
                    "new_filename" => $this->files,
                    "orig_filename" => $this->files,
                ));

                $template->render();
        }


        public function createComponentRenameForm()
        {
                $translator = $this->context->translator;
                $form = new Form;
                $form->setTranslator($translator);
                $form->getElementPrototype()->class("fm-ajax");
                $form->addText("new_filename", "New name")
                        ->addRule(Form::FILLED, "You must fill new name");
                $form->addHidden("orig_filename");
                $form->addSubmit("send", "OK");
                $form->onSuccess[] = array($this, "RenameFormSubmitted");

                return $form;
        }

        public function RenameFormSubmitted($form)
        {
                $translator = $this->context->translator;
                $values = $form->getValues();
                $actualdir = $this->context->system->getActualDir();
                $tools = $this->context->tools;
                $path = $tools->getAbsolutePath($actualdir);

                if ($this->context->parameters["readonly"])
                        parent::getParent()->flashMessage($translator->translate("File manager is in read-only mode"), "warning");
                elseif ($values["new_filename"] == $values["orig_filename"])
                        parent::getParent()->flashMessage($translator->translate("File/folder was not renamed, orignal name = new name"), "warning");
                elseif (file_exists($path . $values["new_filename"]))
                        parent::getParent()->flashMessage($translator->translate("This name was already used. Try another"), "warning");
                elseif (!file_exists($path . $values["orig_filename"]))
                        parent::getParent()->flashMessage($translator->translate("File/folder does not already exists!"), "error");
                else {

                        $origPath = $path . $values["orig_filename"];
                        if (is_dir($tools->getRealPath($origPath))) {

                                $new_filename = $this->context->files->safe_foldername($values["new_filename"]);
                                $this->context->thumbs->deleteDirThumbs($origPath);

                                if ($this->context->parameters["cache"]) {

                                        $caching = $this->context->caching;
                                        $caching->deleteItem(array("content", $tools->getRealPath($path)));
                                        $caching->deleteItemsRecursive($origPath);
                                }
                        } else {

                                $new_filename = $this->context->files->safe_filename($values["new_filename"]);
                                $this->context->thumbs->deleteThumb($tools->getRealPath($origPath));

                                if ($this->context->parameters["cache"]) {

                                        $caching = $this->context->caching;
                                        $caching->deleteItem(array("content", $tools->getRealPath($path)));
                                }
                        }

                        if (rename($origPath, $path . $new_filename)) {

                                parent::getParent()->flashMessage($translator->translate("File/folder name successfully changed."), "info");
                                $this->context->system->clearClipboard();
                        } else
                                parent::getParent()->flashMessage($translator->translate("An error occurred during file/folder renaming"), "error");
                }

                parent::getParent()->handleShowContent($actualdir);
        }
}