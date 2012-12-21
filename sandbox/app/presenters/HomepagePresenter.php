<?php

class HomepagePresenter extends Nette\Application\UI\Presenter
{

    public function createComponentFileManager()
    {
        $config = array(
            "syncResDir" => true,
            "uploadroot" => $this->context->parameters["wwwDir"] . "/data"
        );
        return new Ixtrum\FileManager($this->context, $config);
    }

}