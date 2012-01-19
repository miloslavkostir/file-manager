<?php

namespace Ixtrum;

use Nette\Application\UI\Form;

class Rename extends Ixtrum
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
                $path = $this->context->tools->getAbsolutePath($actualdir);

                if ($this->context->parameters["readonly"])
                        parent::getParent()->flashMessage($translator->translate("File manager is in read-only mode"), "warning");
                elseif ($values["new_filename"] == $values["orig_filename"])
                        parent::getParent()->flashMessage($translator->translate("File/folder was not renamed, orignal name = new name"), "warning");
                elseif (file_exists($path . $values["new_filename"]))
                        parent::getParent()->flashMessage($translator->translate("This name was already used. Try another"), "warning");
                elseif (!file_exists($path . $values["orig_filename"]))
                        parent::getParent()->flashMessage($translator->translate("File/folder does not already exists!"), "error");
                else {

                        if (is_dir($this->context->tools->getRealPath($path . $values["orig_filename"]))) {

                                $new_filename = $this->context->files->safe_foldername($values["new_filename"]);

                                if ($actualdir == $this->context->tools->getRootName())
                                    $thumb_folder = "/" . $values["orig_filename"] . "/";
                                else
                                    $thumb_folder = $actualdir . $values["orig_filename"] . "/" ;

                                $thumb_path = $path . $values["orig_filename"] . "/" . $this->context->files->createThumbFolder($thumb_folder);

                                if (file_exists($thumb_path))
                                    $this->context->files->deleteFolder($thumb_path);

                                if ($this->context->parameters["cache"]) {

                                        $caching = parent::getParent()->context->caching;
                                        $caching->deleteItem(array("content", $this->context->tools->getRealPath($path)));
                                        $caching->deleteItemsRecursive($path . $values["orig_filename"]);
                                }
                        } else {

                                $cache_file =  $this->context->files->createThumbName($actualdir, $values["orig_filename"]);

                                if (file_exists($cache_file["path"]))
                                    unlink($cache_file["path"]);
                                $new_filename = $this->context->files->safe_filename($values["new_filename"]);

                                if ($this->context->parameters["cache"]) {

                                        $caching = parent::getParent()->context->caching;
                                        $caching->deleteItem(array("content", $this->context->tools->getRealPath($path)));
                                }
                        }

                        if (rename($path . $values["orig_filename"], $path . $new_filename)) {

                                parent::getParent()->flashMessage($translator->translate("File/folder name successfully changed."), "info");
                                $this->context->system->clearClipboard();
                        } else
                                parent::getParent()->flashMessage($translator->translate("An error occurred during file/folder renaming"), "error");
                }

                parent::getParent()->handleShowContent($actualdir);
        }
}