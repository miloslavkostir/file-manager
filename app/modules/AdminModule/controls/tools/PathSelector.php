<?php

class Finder extends \Nette\Utils\Finder
{
    private $order;

    /**
     * Sets the order comparison function
     * @param callback $order
     * @return Finder
     */
    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    public function sortDirs()
    {
        $this->order = function($f1, $f2) {
            return \strcasecmp(
                    is_dir($f2->getPathName()),
                    is_dir($f1->getPathName()));
        };
        return $this;
    }

    /**
     * Returns iterator.
     * @return \Iterator
     */
    public function getIterator()
    {
        $iterator = parent::getIterator();
        if ($this->order === NULL) {
            return $iterator;
        }

        $iterator = new \ArrayIterator(\iterator_to_array($iterator));
        $iterator->uasort($this->order);

        return $iterator;
    }
}

class PathSelector extends \Nette\Application\UI\Control
{
    /** @var string */
    public $appPath;

    /** @var bools */
    public $files;

    public function __construct($path, $files = false)
    {
        parent::__construct();
        $this->appPath = $path;
        $this->files = $files;
    }

    public function handleGetTree()
    {
        $path = $this->presenter->context->httpRequest->getPost('path');
        $this->presenter->payload->subtree = utf8_encode($this->getTree($path));
        $this->presenter->sendPayload();
    }

    public function render($id)
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/PathSelector.latte');
        $template->root = $this->getTree($this->appPath);
        $template->inputID = $id;
        $template->appPath = $this->appPath;
        $template->render();
    }

    /**
     * Get files
     * @param string $path
     * @return string
     */
    function getTree($path)
    {
        if (is_dir($path)) {

            if ($this->files === true)
                $files = Finder::find('*');
            else
                $files = Finder::findDirectories('*');
            $files->in($path)->sortDirs();

            $array = iterator_to_array($files);
            if (empty($array))
                return $array;

            $tree = "<ul>";
            foreach ($files as $file) {
                if ($file->isDir())
                    $icon = "ui-icon-folder-collapsed";
                else
                    $icon = "ui-icon-document";

                $tree .= '<li>
                                <span class="ui-icon '.$icon.'"></span>
                                <span id="'.$file->getPathName().'" class="path-selector-link" title="'.$file->getPathName().'">'
                                    .$file->getFilename().
                               '</span>
                         </li>';
            }

            return $tree."</ul>";
        } else
            return false;
    }
}