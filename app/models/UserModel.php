<?php

use Nette\Object;

class UserModel extends Object
{
	public function createAuthenticatorService()
	{
		return new Authenticator($this->getUsers());
	}

        public function addUser($args)
        {
            $authenticator = new \Authenticator($this->getUsers());
            $args['password'] = $authenticator->calculateHash($args['password']);
            dibi::insert('users', $args)->execute();
        }

        public function deleteUser($id)
        {
            dibi::delete('users')->where('id = %i', $id)->execute();
        }

        public function getUser($id)
        {
            return $this->getUsers()->where('id = %i', $id)->limit('1')->fetchAll();
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
                    ->where('id = %i', $id)
                    ->execute();
        }

        /**
         * Check if username exist
         * @param string $username
         * @return bool
         */
        public function usernameExist($username)
        {
            $user = $this->getUsers()->where('username = %s', $username)->fetchAll();
            if (!empty($user))
                return true;
            else
                return false;
        }
}
