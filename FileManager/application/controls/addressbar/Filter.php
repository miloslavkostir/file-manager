<?php

namespace Ixtrum\FileManager\Application\Controls;

use Nette\Application\UI\Form;

class Filter extends \Ixtrum\FileManager
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/Filter.latte");
        $this->template->setTranslator($this->context->translator);
        $this->template->render();
    }

    protected function createComponentFilterForm()
    {
        $form = new Form;
        $form->setTranslator($this->context->translator);
        $form->addText("phrase")
                ->setDefaultValue($this->context->session->get("mask"))
                ->setAttribute("placeholder", $this->context->translator->translate("Filter"));
        $form->onSuccess[] = $this->filterFormSubmitted;
        return $form;
    }

    public function filterFormSubmitted($form)
    {
        $this->context->session->set("mask", $form->values->phrase);
    }

}