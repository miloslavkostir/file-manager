<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Application\UI\Form;

class ViewSelector extends \Ixtrum\FileManager
{

    public function __construct($userConfig)
    {
        parent::__construct($userConfig);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . "/ViewSelector.latte");
        $template->setTranslator($this->context->translator);

        $session = $this->presenter->context->session->getSection("file-manager");
        $this["changeViewForm"]->setDefaults(array("view" => $session->view));

        $template->render();
    }

    protected function createComponentChangeViewForm()
    {
        $translator = $this->context->translator;
        $items = array(
            "large" => $translator->translate("Large images"),
            "small" => $translator->translate("Small images"),
            "list" => $translator->translate("List"),
            "details" => $translator->translate("Details")
        );

        $form = new Form;
        $form->addSelect("view", NULL, $items);
        $form->onSuccess[] = $this->changeViewFormSubmitted;
        return $form;
    }

    public function ChangeViewFormSubmitted($form)
    {
        $val = $form->values;
        $session = $this->presenter->context->session->getSection("file-manager");
        $session->view = $val["view"];
        $this->parent->handleShowContent($this->context->application->getActualDir());
    }

}