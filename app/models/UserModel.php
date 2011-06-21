<?php

use Nette\Object;

class UserModel extends Object
{
	public function getAuthenticatorService()
	{
		return new Authenticator($this->getUsers());
	}

        public function addUser($args)
        {
            $args['password'] = md5($args['password']);
            dibi::insert('users', $args)->execute();
        }

        public function deleteUser($id)
        {
            dibi::delete('users')->where('id = %d', $id)->execute();
        }

        public function getUser($id)
        {
            return $this->getUsers()->where('id = %d', $id)->limit('1')->fetchAll();
        }

        public function getUsers()
        {
            return dibi::select('*')->from('users');
        }

        public function getRoles()
        {
            return dibi::select('*')->from('roles');
        }

        public function updateUser($id, $args)
        {
            dibi::update('users', $args)
                    ->where('id = %d', $id)
                    ->execute();
        }
}
