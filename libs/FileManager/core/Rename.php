<?php

namespace Netfileman;

use Nette\Application\UI\Form;

class Rename extends FileManager
{
    /** @var array */
    public $config;

    /** @var string */
    public $files;

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/Rename.latte');
        $template->setTranslator($this['system']->getTranslator());
        $template->origFile = $this->files;

        $this['renameForm']->setDefaults(array(
            'new_filename' => $this->files,
            'orig_filename' => $this->files,
        ));

        $template->render();
    }

    public function createComponentRenameForm()
    {
        $translator = $this['system']->getTranslator();
        $form = new Form;
        $form->setTranslator($translator);
        $form->getElementPrototype()->class('fm-ajax');
        $form->addText('new_filename', 'New name')
                ->addRule(Form::FILLED, 'You must fill new name');
        $form->addHidden('orig_filename');
        $form->addSubmit('send', 'OK');
        $form->onSuccess[] = array($this, 'RenameFormSubmitted');

        return $form;
    }

    public function RenameFormSubmitted($form)
    {
        $translator = $this['system']->getTranslator();
        $values = $form->getValues();
        $actualdir = $this['system']->getActualDir();
        $path = $this['tools']->getAbsolutePath($actualdir);

        if ($this->config['readonly'] == True)

                        parent::getParent()->flashMessage(
                                $translator->translate("File manager is in read-only mode"),
                                'warning'
                        );

        elseif ($values['new_filename'] == $values['orig_filename'])

                        parent::getParent()->flashMessage(
                                $translator->translate("File/folder was not renamed, orignal name = new name"),
                                'warning'
                        );

        elseif (file_exists($path . $values['new_filename']))

                        parent::getParent()->flashMessage(
                                $translator->translate("This name was already used. Try another"),
                                'warning'
                        );

        elseif (!file_exists($path . $values['orig_filename']))
                        parent::getParent()->flashMessage(
                                $translator->translate("File/folder does not already exists!"),
                                'error'
                        );
        else {

                        if (is_dir( $this['tools']->getRealPath($path . $values['orig_filename']) )) {
                                $new_filename = $this['files']->safe_foldername($values['new_filename']);

                                if ($actualdir == $this['tools']->getRootName())
                                    $thumb_folder = '/' . $values['orig_filename'] . '/';
                                else
                                    $thumb_folder = $actualdir . $values['orig_filename'] . '/' ;

                                $thumb_path = $path . $values['orig_filename'] . '/' . $this['files']->createThumbFolder($thumb_folder);
                                if (file_exists($thumb_path))
                                    $this['files']->deleteFolder($thumb_path);

                                if ($this->config['cache'] == True) {
                                    $this['caching']->deleteItem(array('content', $this['tools']->getRealPath($path)));
                                    $this['caching']->deleteItemsRecursive($path . $values['orig_filename']);
                                }
                        } else {
                                $cache_file =  $this['files']->createThumbName($actualdir, $values['orig_filename']);
                                if (file_exists($cache_file['path']))
                                    unlink($cache_file['path']);
                                $new_filename = $this['files']->safe_filename($values['new_filename']);

                                if ($this->config['cache'] == True)
                                    $this['caching']->deleteItem(array('content', $this['tools']->getRealPath($path)));
                        }

                        if (rename($path . $values['orig_filename'], $path . $new_filename)) {

                                parent::getParent()->flashMessage(
                                        $translator->translate("File/folder name successfully changed."),
                                        'info'
                                );

                                $this['clipboard']->clearClipboard();
                        } else
                                parent::getParent()->flashMessage(
                                        $translator->translate("An error occurred during file/folder renaming"),
                                        'error'
                                );

        }

        parent::getParent()->handleShowContent($actualdir);
    }
}