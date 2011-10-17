<?php

namespace Netfileman;

use Nette\Application\UI\Form;

class NewFolder extends FileManager
{
    /** @var array */
    public $config;

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        $actualdir = $this['system']->getActualDir();

        $template = $this->template;
        $template->setFile(__DIR__ . '/NewFolder.latte');
        $template->setTranslator($this['system']->getTranslator());
        $template->actualdir = $actualdir;

        $this['newFolderForm']->setDefaults(array(
                    'actualdir' => $actualdir
                ));

        $template->render();
    }

    public function  createComponentNewFolderForm()
    {
        $translator = $this['system']->getTranslator();
        $form = new Form;
        $form->setTranslator($translator);
        $form->getElementPrototype()->class('fm-ajax');
        $form->addText('foldername', 'Name of the new folder:')
                ->addRule(Form::FILLED, 'You must fill name of new folder.');
        $form->addHidden('actualdir');
        $form->addSubmit('send', 'Create');
        $form->onSuccess[] = array($this, 'NewFolderFormSubmitted');

        return $form;
    }

    public function NewFolderFormSubmitted($form)
    {
        $translator = $this['system']->getTranslator();
        $values = $form->getValues();

        if ($this->config['readonly'] == True)
                            parent::getParent()->flashMessage(
                                $translator->translate('File manager is in read-only mode'),
                                'warning'
                            );
        else {

                            if ($this['tools']->validPath($values['actualdir'])) {

                                        $foldername = $this['files']->safe_foldername($values['foldername']);

                                        if ($values['actualdir'] == $this['tools']->getRootName()) {
                                            $target_dir = $this->config['uploadroot'] . $this->config['uploadpath'] . $foldername;
                                            $actualdir = "/" . $foldername . "/";
                                        }
                                        else {
                                            $target_dir = $this->config['uploadroot'] . substr($this->config['uploadpath'], 0, -1) . $values['actualdir'] . $foldername;
                                            $actualdir = $values['actualdir']  . $foldername . "/";
                                        }

                                        if ($foldername == "") {
                                                        parent::getParent()->flashMessage(
                                                            $translator->translate('Folder name can not be used. Illegal chars used') . ' \ / : * ? " < > | ..',
                                                            'warning'
                                                        );
                                        } else {

                                            if (file_exists($target_dir))
                                                    parent::getParent()->flashMessage(
                                                        $translator->translate('Folder name already exist. Try choose another'),
                                                        'warning'
                                                    );
                                            else {
                                                    $oldumask = umask(0);
                                                    if (mkdir($target_dir, 0777)) {

                                                        parent::getParent()->flashMessage(
                                                            $translator->translate('Folder successfully created'),
                                                            'info'
                                                        );

                                                        if ($this->config['cache'] == True) {
                                                            $this['caching']->deleteItem(array('content', $this['tools']->getRealPath($this['tools']->getAbsolutePath($values['actualdir']))));
                                                            $this['caching']->deleteItem(NULL, array('tags' => 'treeview'));
                                                        }

                                                        parent::getParent()->handleShowContent($values['actualdir']);   //   TODO replace actualdir with redirect to new created folder

                                                    } else {
                                                        parent::getParent()->flashMessage(
                                                            $translator->translate('An unkonwn error occurred during folder creation'),
                                                            'info'
                                                        );
                                                    }

                                                    umask($oldumask);
                                            }
                                        }
                            }
        }

        parent::getParent()->handleShowContent($values['actualdir']);
    }
}