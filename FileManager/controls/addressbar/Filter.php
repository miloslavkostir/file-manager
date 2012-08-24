<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Application\UI\Form;

class Filter extends \Ixtrum\FileManager
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
        $template->setTranslator($this->context->translator);

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
        $actualdir = $this->context->application->getActualDir();

        $val = $form->values;
        $session->mask = $val["phrase"];
        $this->parent->handleShowContent($actualdir);
    }

}