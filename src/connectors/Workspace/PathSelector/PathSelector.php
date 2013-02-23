<?php

class PathSelector extends \Nette\Application\UI\Control
{

    /** @var string */
    public $appPath;

    /** @var boolean */
    public $showFiles;

    /**
     * Constructor
     *
     * @param string $path      Path to root
     * @param bolean $showFiles Show files
     */
    public function __construct($path, $showFiles = false)
    {
        parent::__construct();
        $this->appPath = $path;
        $this->showFiles = $showFiles;
    }

    /**
     * Directory content handler
     */
    public function handleGetDirContent()
    {
        $path = $this->presenter->context->httpRequest->getPost("path");
        $this->presenter->payload->subtree = utf8_encode($this->getTree($path));
        $this->presenter->sendPayload();
    }

    /**
     * Default render method
     *
     * @param string $id Form input ID
     */
    public function render($id)
    {
        $this->template->setFile(__DIR__ . "/bootstrap.latte");
        $this->template->root = $this->getTree($this->appPath);
        $this->template->inputID = $id;
        $this->template->appPath = $this->appPath;
        $this->template->render();
    }

    /**
     * Get files
     *
     * @param string $path Base path
     *
     * @return string
     */
    function getTree($path)
    {
        if (is_dir($path)) {

            if ($this->showFiles === true) {
                $files = \Nette\Utils\Finder::find("*")->in($path);
            } else {
                $files = \Nette\Utils\Finder::findDirectories("*")->in($path);
            }

            $array = iterator_to_array($files);
            if (empty($array)) {
                return false;
            }

            $tree = '<ul class="nav nav-list">';
            foreach ($files as $file) {
                $icon = "icon-file";
                if ($file->isDir()) {
                    $icon = "icon-folder-close";
                }
                $tree .= '<li>';
                $tree .= '<a data-path="' . $file->getPathName() . '" title="' . $file->getPathName() . '">';
                $tree .= '<i class="' . $icon . '"></i>';
                $tree .= '<span>' . $file->getFilename() . '</span>';
                $tree .= '</a></li>';
            }
            return $tree . "</ul>";
        }
        return false;
    }

}