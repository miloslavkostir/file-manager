<?php

use Nette\Application\Responses\FileResponse;
use Nette\Utils\Finder;
use Nette\Http\Url;

class Player extends FileManager
{
    const NAME = "File Manager Player";

    const VERSION = '0.3 dev';
   
    /** @var string */
    public $actualdir;

    /** @var mixed array, string */
    public $files;

    public function __construct()
    {
        parent::__construct();
    }

    public function handleGetFile($filename, $actualdir)
    {
        if ($actualdir == parent::getParent()->getRootname())
            $path = parent::getParent()->config['uploadroot'] . parent::getParent()->config['uploadpath'] . $filename;
        else
            $path = parent::getParent()->config['uploadroot'] . substr(parent::getParent()->config['uploadpath'], 0, -1) . $actualdir . $filename;

        if ( file_exists($path) )
            $this->presenter->sendResponse(new FileResponse($path, NULL, NULL));
    }

    public function handlePlayMedia($file, $actualdir)
    {
        $this->template->file_media = $file;
        $this->template->playdir = $actualdir;
        $this->invalidateControl('player');
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/Player.latte');
        $template->actualdir = $this->actualdir;
        $template->files = $this->getFiles($this->actualdir);
        $template->render();
    }

    public function getFiles($actualdir)
    {
        if ($actualdir == parent::getParent()->getRootname())
            $path = parent::getParent()->config['uploadroot'] .parent::getParent()->config['uploadpath'];
        else
            $path = parent::getParent()->config['uploadroot'] . substr(parent::getParent()->config['uploadpath'], 0, -1) . $actualdir;

        $files = array();
        foreach (Finder::findFiles('*.mp3')->in($path) as $file) {
            $files[$file->getFilename()]['filename'] = $file->getFilename();
            $files[$file->getFilename()]['actualdir'] = $actualdir;
        }
        
        return $files;
    }

}