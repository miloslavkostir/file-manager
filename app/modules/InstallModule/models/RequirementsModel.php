<?php

namespace InstallModule;

class RequirementsModel extends BaseModel
{
        public function check()
        {
            $status = array();

            if (function_exists('exec'))
                $status[] = array('title' => 'Exec function', 'status' => true);
            else
                $status[] = array('title' => 'Exec function', 'status' => false, 'error' => 'Missing exec function. You will not be able to use iXtrum');

            if (extension_loaded('sqlite'))
                $status[] = array('title' => 'SQLite', 'status' => true);
            else
                $status[] = array('title' => 'SQLite', 'status' => false, 'error' => 'SQLite is not loaded. You will not be able to install iXtrum');

            if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()))
                $status[] = array('title' => 'Rewrite module', 'status' => true);
            else
                $status[] = array('title' => 'Rewrite module', 'status' => false, 'error' => 'Rewrite module is not present. You will not be able to use cool URL');

            if (class_exists('dibi'))
                $status[] = array('title' => 'Dibi', 'status' => true);
            else
                $status[] = array('title' => 'Dibi', 'status' => false, 'error' => 'Dibi is not present. You will not be able to isntall iXtrum');

            return $status;
        }
}