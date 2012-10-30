<?php

class MockSessionSection extends Nette\Object implements \ArrayAccess
{
	public $testedKeyExistence;
	public $storedKey;
	public $storedValue;
	public $testExpiration;
	public $testExpirationVariables;

	public function __isset($name)
	{
		$this->testedKeyExistence = $name;
		return false;
	}

	public function __set($name, $value)
	{
		$this->storedKey = $name;
		$this->storedValue = $value;
	}

	public function setExpiration($expiraton, $variables = NULL)
	{
		$this->testExpiration = $expiraton;
		$this->testExpirationVariables = $variables;
	}

	public function offsetExists($name)
	{
		return $this->__isset($name);
	}

	public function offsetSet($name, $value)
	{
		$this->__set($name, $value);
	}

	public function offsetGet($name) {}
	public function offsetUnset($name) {}
}