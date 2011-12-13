<?php

namespace Netfileman;

use Nette\Application\ApplicationException;

class Loader extends FileManager
{
    /** @var array */
    public $config;

    public function __construct()
    {
        parent::__construct();
    }

    function check()
    {
        $uploadPath = $this->config['uploadroot'] . $this->config['uploadpath'];
        $resDir = WWW_DIR . $this->config['resource_dir'];

        if(!is_dir($uploadPath))
             throw new ApplicationException("Upload path $uploadPath doesn't exist!");

        if (!is_writable($uploadPath))
             throw new ApplicationException("Upload path $uploadPath must be writable!");

        if(!is_dir($uploadPath))
             throw new ApplicationException("Resource path $uploadPath doesn't exist!");
    }
}