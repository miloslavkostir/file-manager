<?php

/**
 * My Application
 *
 * @copyright  Copyright (c) 2010 John Doe
 * @package    MyApplication
 */



/**
 * Homepage presenter.
 *
 * @author     John Doe
 * @package    MyApplication
 */

namespace FrontModule;

class HomepagePresenter extends BasePresenter
{
        /** @var User Model */
        private $umodel;

        /** @var Settings Model */
        private $smodel;

	protected function startup()
	{
		parent::startup();

		// user authentication
		if (!$this->user->isLoggedIn()) {
			if ($this->user->logoutReason === \Nette\Http\User::INACTIVITY) {
				$this->flashMessage('You have been signed out due to inactivity. Please sign in again.');
			}
			$backlink = $this->application->storeRequest();
			$this->redirect('Sign:', array('backlink' => $backlink));
		}
                $this->umodel = new \UserModel;
                $this->smodel = new \SettingsModel;
	}

        public function  createComponentFileManager()
        {
            $conf = $this->umodel->getUser($this->user->id);
            $conf = $conf[0];

            $root = $this->smodel->getRoot($conf->uploadroot);           
            
            $fm = new \FileManager;
            $fm->config['cache'] = $conf->cache;
            $fm->config['uploadroot'] = $root[0]->path;
            $fm->config['uploadpath'] = $conf->uploadpath;
            $fm->config['readonly'] = $conf->readonly;
            $fm->config['quota'] = $conf->quota;
            $fm->config['quota_limit'] = $conf->quota_limit;
            $fm->config['imagemagick'] = $conf->imagemagick;
            $fm->config['lang'] = $conf->lang;
            
            $fm->config['max_upload'] = '1000mb';                // default is 1mb. You can use following examples: 100b, 10kb, 1mb
            $fm->config['upload_chunk'] = True;                 // default is False
            $fm->config['upload_chunk_size'] = '2mb';           // default is 1mb. You can use following examples: 100b, 10kb, 1mb.
            //$fm->config['upload_filter'] = True;                // default is False
            $fm->config['upload_filter_options'][] = array(   // optional
                'title' => 'Image files',
                'extensions' => 'jpg,gif,png'
            );
            $fm->config['upload_filter_options'][] = array(   // optional
                'title' => 'Zip files',
                'extensions' => 'zip'
            );
            $fm->config['upload_resize'] = True;                // default is False
            $fm->config['upload_resize_width'] = 800;           // default is 640
            $fm->config['upload_resize_height'] = 600;          // default is 480
            $fm->config['upload_resize_quality'] = 80;          // default is 90

            //$fm->config['plugins'] = array('Player');           // default is empty

            return $fm;
        }
}