<?php

use Nette\Environment;
use Nette\Utils\Finder;

class FMPlayer extends FileManager
{
    const NAME = "File Manager Player";

    const VERSION = '0.2';

    const DATE = '28.2.2011';
    
    /** @var string */
    public $actualdir;

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
            $this->presenter->sendResponse(new NDownloadResponse($path, NULL, NULL)); 
    }

    public function handlePlayMedia($file, $actualdir)
    {
        $this->template->file_media = $file;
        $this->template->playdir = $actualdir;
        $this->invalidateControl('fmplayer');
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/FMPlayer.latte');
        
        $template->actualdir = $this->actualdir;
        $template->files = $this->getFiles($this->actualdir);
        $template->webadress = Environment::getHttpRequest()->uri->getHostUri();
        $template->config = parent::getParent()->config;

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