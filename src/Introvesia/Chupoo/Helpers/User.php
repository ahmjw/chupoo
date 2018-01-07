<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Introvesia\Chupoo\Helpers;

use stdClass;

class User
{
	const SESSION_NAME = 'system_user_session';
	
	public function isAuth($type = null)
	{
		$session = Session::getInstance();
		if ($session->contain(self::SESSION_NAME)) {
			$user = $session->find(self::SESSION_NAME);
			$type = $type !== null ? $type : Config::find('default_user_session_name');
			if (isset($user[$type])) {
				return true;
			}
		}
		return false;
	}

	public function find($type)
	{
		$session = Session::getInstance();
		if ($session->contain(self::SESSION_NAME)) {
			$user = $session->find(self::SESSION_NAME);
			if (isset($user[$type])) {
				return $user[$type];
			}
		}
		return new UndefinedClass();
	}

	public function findAll()
	{
		$session = Session::getInstance();
		if ($session->contain(self::SESSION_NAME)) {
			return $session->find(self::SESSION_NAME);
		}
	}

	public function save()
	{
		switch (func_num_args()) {
			case 1:
				$type = Config::find('default_user_session_name');
				$data = func_get_arg(0);
				break;
			case 2:
				$type = func_get_arg(0);
				$data = func_get_arg(1);
				break;
			default:
				throw new Exception('Bad argument', 500);
		}
		$session = Session::getInstance();
		if ($session->contain(self::SESSION_NAME)) {
			$user = $session->find(self::SESSION_NAME);
			$user[$type] = $data;
			$session->save(self::SESSION_NAME, $user);
		} else {
			$session->save(self::SESSION_NAME, array($type => $data));
		}
	}

	public function clear()
	{
		Session::getInstance()->clear(self::SESSION_NAME);
	}
}