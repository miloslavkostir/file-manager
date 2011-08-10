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
		$row = $this->users->where('username = %s', $username)->fetch();

		if (!$row)
			throw new NS\AuthenticationException("Invalid username or password.", self::INVALID_CREDENTIAL);

		if ($row->password !== $this->calculateHash($password))
			throw new NS\AuthenticationException("Invalid username or password.", self::INVALID_CREDENTIAL);

		unset($row->password);
		return new NS\Identity($row->id, $row->role, $row->toArray());
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
