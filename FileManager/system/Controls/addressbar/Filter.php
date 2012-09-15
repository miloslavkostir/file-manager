<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Application\UI\Form;

class Filter extends \Ixtrum\FileManager
{

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . "/Filter.latte");
        $template->setTranslator($this->context->translator);

        $this["filterForm"]->setDefaults(array("phrase" => $this->context->session->get("mask")));

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
        $this->context->session->set("mask", $form->values->phrase);
    }

}