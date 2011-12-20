<?php

namespace FrontModule;

class HomepagePresenter extends BasePresenter
{
	protected function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn())
			$this->redirect('Sign:');
	}

        public function  createComponentFileManager()
        {
                $conf = $this->models->UserModel->getUser($this->user->id);

                if (!$conf)
                        throw new \Nette\InvalidArgumentException("User not found!");

                if ($conf->has_share <> true)
                        throw new \Nette\Application\ForbiddenRequestException ("User is not allowed to have a share!", 403);

                $root = $this->models->SettingsModel->getRoot($conf->uploadroot);           
                if (!$root)
                        throw new \Nette\InvalidArgumentException("Upload root not defined!");

                $fm = new \Netfileman\FileManager;
                $fm->config['cache'] = $conf->cache;
                $fm->config['uploadroot'] = $root->path;
                $fm->config['uploadpath'] = $conf->uploadpath;
                $fm->config['readonly'] = $conf->readonly;
                $fm->config['quota'] = $conf->quota;
                $fm->config['quota_limit'] = $conf->quota_limit;
                $fm->config['lang'] = $conf->lang;

                return $fm;
        }
}