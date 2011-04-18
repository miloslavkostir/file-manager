<?php

use Nette\Application\UI\Form;

class FMRename extends FileManager
{
    /** @var array */
    public $config;

    /** @var array */
    public $params;

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/FMRename.latte');

        // set language
        $lang_file = __DIR__ . '/../locale/FileManager.'. $this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");

        $this['renameForm']->setDefaults($this->params);

        $template->config = $this->config;
        $template->params = $this->params;

        $template->render();
    }

    public function createComponentRenameForm()
    {
        $translator = new GettextTranslator(__DIR__ . '/../locale/FileManager.' . $this->config['lang'] . '.mo');
        $form = new Form;
        $form->setTranslator($translator);
        $form->getElementPrototype()->class('fm-ajax');
        $form->addText('new_filename', 'New name')
                ->addRule(Form::FILLED, 'You must fill new name');
        $form->addHidden('actualdir');
        $form->addHidden('orig_filename');
        $form->addSubmit('send', 'OK');
        $form->onSubmit[] = array($this, 'RenameFormSubmitted');

        return $form;
    }

    public function RenameFormSubmitted($form)
    {
        $translator = new GettextTranslator(__DIR__ . '/../locale/FileManager.' . $this->config["lang"] . '.mo');

        $values = $form->getValues();

        $path = parent::getParent()->getAbsolutePath($values['actualdir']);

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

        elseif (!file_exists($path . $values['orig_filename'])) {
                        // refresh folder content cache
                        $this['tools']->clearFromCache(array('fmfiles', $values['actualdir']));
                        $this['tools']->clearFromCache('fmtreeview');

                        parent::getParent()->flashMessage(
                                $translator->translate("File/folder does not already exists!"),
                                'error'
                        );
        } else {

                        if (is_dir( realpath($path . $values['orig_filename']) )) {
                                $new_filename = $this['fmFiles']->safe_foldername($values['new_filename']);

                                // delete thumb folder & clear old folder cache
                                if ($values['actualdir'] == parent::getParent()->getRootname()) {
                                    $thumb_folder = '/' . $values['orig_filename'] . '/';
                                    $this['tools']->clearFromCache(array('fmfiles', '/' . $values['orig_filename'] . '/'));
                                } else {
                                    $thumb_folder = $values['actualdir'] . $values['orig_filename'] . '/' ;
                                    $this['tools']->clearFromCache(array('fmfiles', $values['actualdir'] . $values['orig_filename'] . '/'));
                                }

                                $thumb_path = $path . $values['orig_filename'] . '/' . $this['fmFiles']->createThumbFolder($thumb_folder);
                                if (file_exists($thumb_path))
                                    $this['fmFiles']->deleteFolder($thumb_path);
                        } else {
                                $cache_file =  $this['fmFiles']->createThumbName($values['actualdir'], $values['orig_filename']);
                                if (file_exists($cache_file['path']))
                                    unlink($cache_file['path']);
                                $new_filename = $this['fmFiles']->safe_filename($values['new_filename']);
                        }

                        if (rename($path . $values['orig_filename'], $path . $new_filename)) {
                                $this['tools']->clearFromCache(array('fmfiles', $values['actualdir']));
                                $this['tools']->clearFromCache('fmtreeview');

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
        
        parent::getParent()->handleShowContent($values['actualdir']);
    }
}