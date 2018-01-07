<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Introvesia\Chupoo\Helpers;

class Config
{
	private static $config = array(
		'default_user_session_name' => 'admin',
		'debug_mode' => false,
	);

	public static function prepend()
	{
		switch(func_num_args()) {
			case 1:
				self::$config = array_merge(func_get_arg(0), self::$config);
				break;
			case 2:
				$name = func_get_arg(0);
				self::$config[$name] = array_merge(func_get_arg(1), self::$config[$name]);
				break;
		}
	}

	public static function append()
	{
		switch(func_num_args()) {
			case 1:
				self::$config = array_merge(self::$config, func_get_arg(0));
				break;
			case 2:
				$name = func_get_arg(0);
				self::$config[$name] = array_merge(self::$config[$name], func_get_arg(1));
				break;
		}
	}

	public static function save($key, $value)
	{
		self::$config[$key] = $value;
	}

	public static function contain($key)
	{
		return isset(self::$config[$key]);
	}

	public static function find($key)
	{
		if (isset(self::$config[$key])) {
			return self::$config[$key];
		}
	}

	public static function findAll()
	{
		return self::$config;
	}

	public static function getPath($name)
	{
		return self::find('app_path') . DIRECTORY_SEPARATOR . 'config' .
			DIRECTORY_SEPARATOR . $name . '.php';
	}

	public static function read($name)
	{
		$path = self::find('app_path') . DIRECTORY_SEPARATOR . 'config' .
			DIRECTORY_SEPARATOR . $name . '.php';
		if (!file_exists($path)) {
			throw new \Exception('Config file not found: ' . $name . '.php', 500);
		}
		return include($path);
	}

	public static function property()
	{
		switch(func_num_args()) {
			case 0:
				return self::$config['properties'];
			case 1:
				$name = func_get_arg(0);
				if (is_string($name)) {
					return isset(self::$config['properties'][$name]) ? self::$config['properties'][$name] : null;
				} else {
					$properties = array_merge(self::$config['properties'], $name);
					self::$config['properties'] = $properties;
				}
				break;
			case 2:
				self::$config['properties'][func_get_arg(0)] = func_get_arg(1);
				break;
			default:
				throw new Exception('Bad argument', 500);
		}
	}
}