<?php

class HomepagePresenter extends Nette\Application\UI\Presenter
{

    public function createComponentFileManager()
    {
        $config = array(
            "uploadroot" => $this->context->parameters["wwwDir"]
        );
        return new Ixtrum\FileManager($this->context, $config);
    }

}