<?php

class HomepagePresenter extends Nette\Application\UI\Presenter
{

    public function createComponentFileManager()
    {
        return new Ixtrum\FileManager($this->context);
    }

}