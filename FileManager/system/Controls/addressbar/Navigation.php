<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Application\UI\Form;

class Navigation extends \Ixtrum\FileManager
{

    public function handleRefreshContent()
    {
        $actualdir = $this->context->session->get("actualdir");
        if ($this->context->parameters["cache"]) {

            $this->context->caching->deleteItem(NULL, array("tags" => "treeview"));
            $this->context->caching->deleteItem(array(
                "content",
                $this->context->filesystem->getRealPath($this->context->filesystem->getAbsolutePath($actualdir))
            ));
        }

        $this->parent->parent->handleShowContent($actualdir);
    }

    public function render()
    {
        $actualdir = $this->context->session->get("actualdir");
        $rootname = $this->context->filesystem->getRootName();

        $template = $this->template;
        $template->setFile(__DIR__ . '/Navigation.latte');
        $template->setTranslator($this->context->translator);

        if (!$actualdir) {
            $template->items = $this->getNav($rootname);
        } else {
            $template->items = $this->getNav($actualdir);
        }

        $this['locationForm']->setDefaults(array('location' => $actualdir));

        $template->render();
    }

    protected function createComponentLocationForm()
    {
        $form = new Form;
        $form->setTranslator($this->context->translator);
        $form->addText('location');
        $form->onSuccess[] = $this->locationFormSubmitted;
        return $form;
    }

    public function locationFormSubmitted($form)
    {
        $val = $form->values;
        $path = $this->context->filesystem->getAbsolutePath($val['location']);

        if (is_dir($path)) {
            $this->parent->parent->handleShowContent($val['location']);
        } else {
            $folder = $val['location'];
            $this->parent->parent->flashMessage($this->context->translator->translate("Folder %s does not exist!", $folder), 'warning');
            $this->parent->parent->handleShowContent($this->context->session->get("actualdir"));
        }
    }

    public function getNav($actualdir)
    {
        $var = array();
        $rootname = $this->context->filesystem->getRootName();
        if ($actualdir === $rootname)
            $var[] = array(
                "name" => $rootname,
                "link" => $this->parent->parent->link("showContent", $rootname)
            );
        else {
            $nav = explode("/", $actualdir);
            $path = "/";
            foreach ($nav as $item) {
                if ($item) {
                    $path .= "$item/";
                    $var[] = array(
                        "name" => $item,
                        "link" => $this->parent->parent->link("showContent", $path)
                    );
                }
            }
        }

        return $var;
    }

}