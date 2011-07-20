<?php

/**
 * NetFileMan
 *
 * @copyright  Copyright (c) 2011 Bauer01
 * @package    NeFileMan
 */



/**
 * Homepage presenter.
 *
 * @author     Bauer01
 * @package    NeFileMan
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

		if (!$this->user->isLoggedIn())
			$this->redirect('Sign:');

                $this->umodel = new \UserModel;
                $this->smodel = new \SettingsModel;
	}

        public function  createComponentFileManager()
        {
            $conf = $this->umodel->getUser($this->user->id);

            if (!$conf)
                    throw new \Nette\InvalidArgumentException("User not found!");

            $conf = $conf[0];
            if ($conf->has_share <> true)
                    throw new \Nette\Application\ForbiddenRequestException ("User is not allowed to have a share!", 403);

            $root = $this->smodel->getRoot($conf->uploadroot);           
            if (!$root)
                    throw new \Nette\InvalidArgumentException("Upload root not defined!");

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