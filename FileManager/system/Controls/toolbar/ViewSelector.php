<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Application\UI\Form;

class ViewSelector extends \Ixtrum\FileManager
{

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . "/ViewSelector.latte");
        $template->setTranslator($this->context->translator);

        $this["changeViewForm"]->setDefaults(array("view" => $this->context->session->get("view")));

        $template->render();
    }

    protected function createComponentChangeViewForm()
    {
        $items = array(
            "large" => $this->context->translator->translate("Large images"),
            "small" => $this->context->translator->translate("Small images"),
            "list" => $this->context->translator->translate("List"),
            "details" => $this->context->translator->translate("Details")
        );

        $form = new Form;
        $form->addSelect("view", NULL, $items);
        $form->onSuccess[] = $this->changeViewFormSubmitted;
        return $form;
    }

    public function changeViewFormSubmitted($form)
    {
        $this->context->session->set("view", $form->values->view);
    }

}