<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Application\UI\Form;

class Filter extends \Ixtrum\FileManager
{

    public function __construct($config)
    {
        parent::__construct($config);
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
        $form = new Form;
        $form->setTranslator($this->context->translator);
        $form->addText("phrase")->getControlPrototype()->setTitle($this->context->translator->translate("Filter"));
        $form->onSuccess[] = $this->filterFormSubmitted;
        return $form;
    }

    public function filterFormSubmitted($form)
    {
        $session = $this->presenter->context->session->getSection("file-manager");
        $actualdir = $this->context->application->getActualDir();

        $val = $form->values;
        $session->mask = $val["phrase"];
        $this->parent->parent->handleShowContent($actualdir);
    }

}