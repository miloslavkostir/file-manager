<?php

use Nette\Utils\Finder;

class PathSelector extends \Nette\Application\UI\Control
{
    /** @var string */
    public $appPath;

    public function __construct($path)
    {
        parent::__construct();
        $this->appPath = $path;
    }

    public function handleShowSubTree()
    {
        $path = $this->presenter->context->httpRequest->getPost('path');
        $this->presenter->payload->subtree = $this->getSubTree($path);
        $this->presenter->sendPayload();
    }

    public function render($id)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/FolderSelector.latte');
        $template->root = $this->getSubTree($this->appPath);
        $template->inputID = $id;
        $template->appPath = $this->appPath;
        $template->render();
    }

    /**
     * Get sub-folder tree
     * @param string $path
     * @return string
     */
    function getSubTree($path)
    {
        if (is_dir($path)) {
            $tree = "<ul>";
            $dirs = Finder::findDirectories('*')
                    ->in($path);

            foreach ($dirs as $dir) {
                $tree .= '<li>
                            <span class="ui-icon ui-icon-folder-collapsed"></span>
                            <span id="'.$dir->getPathName().'" class="folder-selector-link" title="'.$dir->getPathName().'">'
                                .$dir->getFilename().'
                            </span>
                          </li>';
            }
            return $tree."</ul>";
        } else
            return false;
    }
}