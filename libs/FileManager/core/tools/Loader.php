<?php

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
        $resDir = $this->presenter->context->params['wwwDir'] . $this->config['resource_dir'];

        if (!function_exists("exec"))
             throw new Exception ("Missing exec function!");

        if(!is_dir($uploadPath))
             throw new Exception ("Upload path $uploadPath doesn't exist!");

        if (!is_writable($uploadPath))
             throw new Exception ("Upload path $uploadPath must be writable!");

        if(!is_dir($uploadPath))
             throw new Exception ("Resource path $uploadPath doesn't exist!");
    }
}