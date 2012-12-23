<?php

class HomepagePresenter extends Nette\Application\UI\Presenter
{

    public function createComponentFileManager()
    {
        $config = array(
            "uploadroot" => $this->context->parameters["wwwDir"] . "/data"
        );
        $fm = new Ixtrum\FileManager($this->context, $config);
        $fm->syncResources();
        return $fm;
    }

}