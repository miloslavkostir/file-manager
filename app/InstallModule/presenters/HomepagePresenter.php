<?php

namespace InstallModule;

use Nette\Config\Loader,
        Nette\Application as NA,
        Nette\Caching\Cache;

class HomepagePresenter extends BasePresenter
{
        /** @var array */
        private $progress;

        /** @var string */
        private $customNeon;

	protected function startup()
	{
		parent::startup();
                $this->customNeon = $this->context->parameters["configDir"] . "custom.neon";
                $this->progress = $this->context->parameters["install"];
                if ($this->progress["finished"])
                    $this->redirect(":Admin:Overview:");
	}

        public function renderFinish()
        {
                if (!$this->progress['requirements'])
                    throw new NA\ForbiddenRequestException("Your server does not meet minimum requirements");
                elseif ($this->progress['errors'] == true)
                    throw new NA\ApplicationException("An error occured during the install.");
                else {
                    $storage = $this->context->parameters["storageDir"];
                    $db = $storage . "database.db";
                    $sql = $storage . "default.sql";

                    $model = new InstallModel($this->context);
                    $model->createDB($db);

                    $import = $model->importDB($db, $sql);
                    $this->template->import = $import;


                    $this->progress["finished"] = true;

                    $this->saveProgress();

                    $fp = fopen($sql, "r");
                    $this->template->sql = fread($fp, filesize($sql));
                    fclose($fp);
                }
        }

        public function renderRequirements()
        {
                $model = new RequirementsModel($this->context);
                $requirements = $model->check();
                $this->template->status = $requirements;

                foreach ($requirements as $item) {
                    if (!$item['status'])
                        $errors = true;
                }

                if (!isset($errors))
                    $this->progress['requirements'] = true;
                
                $this->saveProgress();
        }

        function saveProgress()
        {
                $loader = new Loader;
                $config = $loader->load($this->customNeon);
                $config["parameters"]["install"] = $this->progress;
                $loader->save($config, $this->customNeon);
                $this->context->cacheStorage->clean(array(Cache::ALL => true));
        }
}