<?php

namespace InstallModule;

use \Nette\Config\NeonAdapter,
        \Nette\Diagnostics\Debugger;

class HomepagePresenter extends BasePresenter
{
        /** @var array */
        private $progress;

	protected function startup()
	{
		parent::startup();
                $this->progress = NeonAdapter::load($this->context->params["appDir"] . "/storage/install.neon");
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
                    $storage = $this->context->params["appDir"] . "/storage/";
                    $db = $storage . "database.db";
                    $sql = $storage . "default.sql";

                    $model = new InstallModel;
                    $model->createDB($db);
                    Debugger::log("Database '$db' created");

                    $import = $model->importDB($db, $sql);
                    Debugger::log("SQL dump '$sql' imported");

                    $this->progress['finished'] = true;

                    NeonAdapter::save($this->progress, $this->context->params["appDir"] . "/storage/install.neon");
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

                NeonAdapter::save($this->progress, $this->context->params["appDir"] . "/storage/install.neon");
        }
}