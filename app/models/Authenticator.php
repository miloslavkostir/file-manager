<?php

use Nette\Security as NS;


/**
 * Users authenticator.
 */
class Authenticator extends Nette\Object implements NS\IAuthenticator
{
	/** @var DibiFluent */
	private $users;

	public function __construct(DibiFluent $users)
	{
		$this->users = $users;
	}

	/**
	 * Performs an authentication
	 * @param  array
	 * @return IIdentity
	 * @throws AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		list($username, $password) = $credentials;
		$row = $this->users->where('username = %s', $username)->fetchAll();
                $row = $row[0];

		if (!$row) {
			throw new NS\AuthenticationException("User '$username' not found.", self::IDENTITY_NOT_FOUND);
		}

		if ($row->password !== $this->calculateHash($password)) {
			throw new NS\AuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
		}

		unset($row->password);
		return new NS\Identity($row->id, NULL, $row->toArray());
	}

	/**
	 * Computes salted password hash.
	 * @param  string
	 * @return string
	 */
	public function calculateHash($password)
	{
		return md5($password);
	}
}
