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

            return $fm;
        }
}