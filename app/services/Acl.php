<?php

use Nette\Security\Permission;

class Acl extends Permission implements \Nette\Security\IAuthorizator
{
        /** @var array */
        public $roles = array(
            'admin' => 'Administrator',
            'user' => 'User'
        );

        public function __construct()
        {
                $this->addRole('user');
                $this->addRole('admin');

                $this->addResource('Admin');

                $this->allow('admin', Permission::ALL, Permission::ALL);
        }
}