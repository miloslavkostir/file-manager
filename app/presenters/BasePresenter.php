<?php

abstract class BasePresenter extends Nette\Application\UI\Presenter
{
        /**
         * @return \ModelLoader
         */
        final public function getModels()
        {
            return $this->context->modelLoader;
        }
}