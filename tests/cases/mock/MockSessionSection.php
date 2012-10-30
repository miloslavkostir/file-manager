<?php

class MockUser extends Nette\Security\User
{
	public function __construct() {}

	public function getId()
	{
		return 'test_id';
	}
}