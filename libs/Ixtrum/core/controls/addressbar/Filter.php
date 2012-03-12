<?php

namespace Ixtrum;

use Nette\Application\UI\Form;


class Filter extends FileManager
{
        public function __construct($userConfig)
        {
                parent::__construct($userConfig);
        }


        public function render()
        {
                $session = $this->presenter->context->session->getSection("file-manager");

                $template = $this->template;
                $template->setFile(__DIR__ . "/Filter.latte");

                $this["filterForm"]->setDefaults(array("phrase" => $session->mask));

                $template->render();
        }


        protected function createComponentFilterForm()
        {
                $translator = $this->context->translator;
                $form = new Form;
                $form->setTranslator($translator);
                $form->addText("phrase")->getControlPrototype()->setTitle($translator->translate("Filter"));
                $form->onSuccess[] = callback($this, "FilterFormSubmitted");

                return $form;
        }


        public function FilterFormSubmitted($form)
        {
                $session = $this->presenter->context->session->getSection("file-manager");
                $actualdir = $this->context->system->getActualDir();

                $val = $form->values;
                $session->mask = $val["phrase"];
                parent::getParent()->handleShowContent($actualdir);
        }
}