<?php

use Nette\Security\Permission;

class Acl extends Permission implements \Nette\Security\IAuthorizator
{
        /** @var array */
        public $roles = array(
                "user" => "User",
                "admin" => "Administrator",
                "root" => "Root"
        );

        public function __construct()
        {
                foreach ($this->roles as $role => $key) {
                    $this->addRole($role);
                }

                $this->addResource("Admin");    // Access to admin module
                $this->addResource("server_settings");   // Access to server settings

                $this->allow("root", Permission::ALL, Permission::ALL);
                $this->allow("admin", "Admin");
        }
}