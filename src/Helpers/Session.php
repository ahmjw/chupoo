<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Chupoo\Helpers;

class Session
{
	private static $instance;

	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function merge(array $config)
	{
		$_SESSION = array_merge($_SESSION, $config);
	}

	public function save($key, $value)
	{
		$_SESSION[$key] = $value;
	}

	public function clear($key)
	{
		unset($_SESSION[$key]);
	}

	public function contain($key)
	{
		return isset($_SESSION[$key]);
	}

	public function find($key)
	{
		return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
	}

	public function findAll()
	{
		return $_SESSION;
	}
}