<?php

namespace InstallModule;

use \Nette\Config\Loader,
        \Nette\Diagnostics\Debugger;

class HomepagePresenter extends BasePresenter
{
        /** @var array */
        private $progress;

	protected function startup()
	{
		parent::startup();
                $loader = new Loader;
                $this->progress = $loader->load(APP_DIR . "/storage/install.neon");
                if ($this->progress['finished'] == true)
                    $this->redirect(':Admin:Overview:');
	}

        public function renderFinish()
        {
                if (!$this->progress['requirements'])
                    throw new \Nette\Application\ForbiddenRequestException("Your server does not meet minimum requirements");
                elseif ($this->progress['errors'] == true)
                    throw new \Nette\Application\ApplicationException("An error occured during the install.");
                else {
                    $storage = APP_DIR . "/storage/";
                    $db = $storage . "database.db";
                    $sql = $storage . "default.sql";

                    $model = new InstallModel;
                    $model->createDB($db);
                    Debugger::log("Database '$db' created");

                    $import = $model->importDB($db, $sql);
                    Debugger::log("SQL dump '$sql' imported");

                    $this->progress['finished'] = true;

                    $loader = new Loader;
                    $loader->save($this->progress, APP_DIR . "/storage/install.neon");
                    Debugger::log("Installation successfully finished");

                    $this->template->import = $import;

                    $fp = fopen($sql, "r");
                    $this->template->sql = fread($fp, filesize($sql));
                    fclose($fp);
                }
        }

        public function renderRequirements()
        {
                $model = new RequirementsModel();
                $requirements = $model->check();
                $this->template->status = $requirements;

                foreach ($requirements as $item) {
                    if (!$item['status'])
                        $errors = true;
                }

                if (isset($errors))
                    $this->progress['requirements'] = false;
                else
                    $this->progress['requirements'] = true;

                $loader = new Loader;
                $loader->save($this->progress, APP_DIR . "/storage/install.neon");
        }
}