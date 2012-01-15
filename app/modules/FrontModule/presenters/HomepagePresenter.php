<?php

namespace FrontModule;

use Nette\InvalidArgumentException;


class HomepagePresenter extends BasePresenter
{
	protected function startup()
	{
		parent::startup();

                $user = $this->user;
                if (!$user->isLoggedIn()) {

			if ($user->logoutReason === \Nette\Http\User::INACTIVITY)
				$this->flashMessage("You have been signed out due to inactivity. Please sign in again.");
			$backlink = $this->application->storeRequest();
                        $this->redirect("Sign:", array("backlink" => $backlink));
                }
	}

        protected function  createComponentFileManager()
        {
                $conf = $this->models->UserModel->getUser($this->user->id);
                if (!$conf)
                        throw new InvalidArgumentException("User not found!");

                if (!$conf->has_share)
                        throw new \Nette\Application\ForbiddenRequestException ("User is not allowed to have a share!", 403);

                $root = $this->models->SettingsModel->getRoot($conf->uploadroot);           
                if (!$root)
                        throw new InvalidArgumentException("Upload root not defined!");

                $config = array();
                $config["cache"] = $conf->cache;
                $config["uploadroot"] = $root->path;
                $config["uploadpath"] = $conf->uploadpath;
                $config["readonly"] = $conf->readonly;
                $config["quota"] = $conf->quota;
                $config["quota_limit"] = $conf->quota_limit;
                $config["lang"] = $conf->lang;

                $netfileman = new \Netfileman\Netfileman($config);

                return $netfileman;
        }
}