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

use Nette\Application\UI\Control;

class HomepagePresenter extends BasePresenter
{
        public function  createComponentFileManager()
        {
            $fm = new FileManager;

            $fm->config['cache'] = False;                     // default is True
            //$fm->config['resource_dir'] = '/resources/';      // default is /fm-src/ (must be located in WWW_DIR)
            $fm->config['uploadroot'] = WWW_DIR;
            //$fm->config['uploadroot'] = APP_DIR;
            $fm->config['uploadpath'] = '/data/';               // ! DO NOT forget slashes !
            //$fm->config['uploadpath'] = '/data/bronek/';        // ! DO NOT forget slashes !
            //$fm->config['readonly'] = True;                   // default is False

            $fm->config['quota'] = True;                        // default is False
            $fm->config['quota_limit'] = 20;                   // default is 20; size is in MB

            $fm->config['max_upload'] = '1000mb';                // default is 1mb. You can use following examples: 100b, 10kb, 1mb
            $fm->config['upload_chunk'] = True;                 // default is False
            $fm->config['upload_chunk_size'] = '2mb';           // default is 1mb. You can use following examples: 100b, 10kb, 1mb.
            //$fm->config['upload_filter'] = True;                // default is False
            $fm->config['upload_filter_options'][] = array(   // optional
                'title' => 'Image files',
                'extensions' => 'jpg,gif,png'
            );
            $fm->config['upload_filter_options'][] = array(   // optional
                'title' => 'Zip files',
                'extensions' => 'zip'
            );
            $fm->config['upload_resize'] = True;                // default is False
            $fm->config['upload_resize_width'] = 800;           // default is 640
            $fm->config['upload_resize_height'] = 600;          // default is 480
            $fm->config['upload_resize_quality'] = 80;          // default is 90

            //$fm->config['imagemagick'] = True;                // default is False

            $fm->config['lang'] = 'en';                         // default is en; others - cs

            //$fm->config['plugins'] = array('Player');           // default is empty

            return $fm;
        }
}
