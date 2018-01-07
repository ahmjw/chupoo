<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Introvesia\Chupoo;

use Exception;
use Introvesia\Chupoo\Helpers\Config;
use Introvesia\Chupoo\Controllers\Hmvc;
use Introvesia\Chupoo\Controllers\Controller;

class Starter
{
	private static $controller;

	public function __construct(array $config)
	{
		$config['max_route_depth'] = 10;
		spl_autoload_register(array($this, 'autoload'));
		set_exception_handler(array($this, 'exception'));
		// Load directory
		$config['dir'] = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
		// Load URI
		$uris = explode('?', $_SERVER['REQUEST_URI'], 2);
        $config['uri'] = substr($uris[0], strlen($config['dir'] . '/'), strlen($uris[0]));
        // Load base URL
        $host = $_SERVER['HTTP_HOST'];
        $protocol = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ? 'https' : 'http';
        $path = str_replace('/'.basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['PHP_SELF']);
        $config['host_url'] = $protocol . '://' . $host;
        $config['app_path'] = $config['base_path'] . DIRECTORY_SEPARATOR . 'app';
        $config['base_url'] = $config['host_url'] . $path;
        if (isset($config['core_dir']) && isset($config['app_dir'])) {
        	$config['app_path'] = $config['base_path'] . DIRECTORY_SEPARATOR . $config['core_dir'] . DIRECTORY_SEPARATOR . 'projects' . 
        	DIRECTORY_SEPARATOR . $config['app_dir'] . DIRECTORY_SEPARATOR . 'app';
        	$config['app_path'] = str_replace('/', DIRECTORY_SEPARATOR, $config['app_path']);
        	$config['theme_path'] = $config['base_path'] . DIRECTORY_SEPARATOR . $config['core_dir'] . DIRECTORY_SEPARATOR . 'themes';
        	$config['theme_path'] = str_replace('/', DIRECTORY_SEPARATOR, $config['theme_path']);
        	$config['theme_url'] = preg_replace('/\/[a-zA-Z0-9_]+\/[a-zA-Z0-9_]+$/', '', $config['base_url']) . '/' . $config['core_dir'];
        } else {
        	$config['app_path'] = $config['base_path'] . DIRECTORY_SEPARATOR . 'app';
        	$config['theme_path'] = realpath($config['base_path'] . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..') . 
        	DIRECTORY_SEPARATOR . 'themes';
        	$config['theme_url'] = preg_replace('/\/[a-zA-Z0-9_]+\/[a-zA-Z0-9_]+$/', '', $config['base_url']);
        }
        $config['absolute_url'] = $config['base_url'];
        $config['config_path'] = $config['app_path'] . DIRECTORY_SEPARATOR . 'config';
        $config['module_path'] = $config['app_path'] . DIRECTORY_SEPARATOR . 'modules';
        $config['model_path'] = $config['app_path'] . DIRECTORY_SEPARATOR . 'models';
        $config['url'] = $config['base_url'] . '/' . $config['uri'];
        
		Config::save('sys_src_path', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR));
		Config::append($config);
		Config::append(Config::read('defined'));
		Config::append(array(
			'access' => Config::read('access')
		));
		Config::append(array(
			'alias' => Config::read('route_aliasing')
		));
		Config::append(array(
			'properties' => Config::read('properties')
		));
	}

	public static function getController()
	{
		return self::$controller;
	}

	public function run()
	{
		if (Config::contain('parent_controller')) {
        	$class_name = Config::find('parent_controller');
        	self::$controller = new $class_name;
        } else {
        	self::$controller = new Controller();
        }
        if (Config::contain('layout')) {
        	self::$controller->layout = Config::find('layout');
        }
    	if (file_exists(Config::getPath('properties'))) {
			$properties = Config::find('properties');
			foreach ($properties as $key => $value) {
				self::$controller->{$key} = $value;
			}
		}
        try {
        	self::$controller->render(Config::find('uri'));
		} catch (Exception $e) {
			$this->exception($e);
		}
	}

	public function debug()
	{
		if (Config::contain('parent_controller')) {
        	$class_name = Config::find('parent_controller');
        	self::$controller = new $class_name;
        } else {
        	self::$controller = new Controller();
        }
        if (Config::contain('theme')) {
        	self::$controller->theme = Config::find('theme');
        }
        if (Config::contain('layout')) {
        	self::$controller->layout = Config::find('layout');
        }
	}

	public function normalizePath($path)
	{
		return str_replace('\\', DIRECTORY_SEPARATOR, str_replace('/', DIRECTORY_SEPARATOR, $path));
	}

	public function autoload($class_name)
	{
		$class_name = $this->normalizePath($class_name);
		$path = Config::find('app_path') . DIRECTORY_SEPARATOR . 'libraries' .
			DIRECTORY_SEPARATOR . $class_name . '.php';
		if (!file_exists($path)) {
			if (!preg_match('/^Models\\\\.*?$/', $class_name)) {
				$path = __DIR__ . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . $class_name . '.php';
				if (!file_exists($path)) {
					$path = self::$controller != null ? self::abbrPath($path) : $path;
					throw new Exception("Library file is not found: " . $path, 500);
				}
			} else {
				throw new Exception("Model class is not found: $class_name", 500);
			}
		}
		require_once($path);
	}

	public function exception($exc)
	{
		if (self::$controller != null) {
			if (self::$controller->content_type === 'html') {
				if ($exc->getCode() == 403) {
					if (Config::contain('login_route')) {
						if (Config::find('login_route') != self::$controller->route) {
							self::$controller->redirect('/' . Config::find('login_route'));
						} else {
							self::$controller->layout = 'index';
							self::$controller->render('index/_error', array($exc));
						}
					} else {
						self::$controller->layout = 'index';
						self::$controller->render('index/_error', array($exc), Hmvc::TYPE_WIDGET);
					}
				} else if (self::$controller === null) {
					$controller = new Controller();
					$this->render($controller, $exc);
				} else {
					try{
						self::$controller->layout = 'index';
						self::$controller->render('index/_error', array($exc), Hmvc::TYPE_WIDGET);
					} catch(Exception $ex) {
						$controller = new Controller();
						$this->render($controller, $ex);
					}
				}
			} else if (self::$controller->content_type === 'json') {
				header('Content-type: application/json');
				echo json_encode(array(
					'error' => array(
						'code' => $exc->getCode(),
						'message' => $exc->getMessage(),
					),
				));
			}
		} else {
			echo '<html><head><title>Error ' . $exc->getCode() . '</title></head>';
			echo '<body><h1>Error ' . $exc->getCode() . '</h1><p>' . $exc->getMessage() . '</p></body></html>';
		}
	}

	private function render($controller, $exc)
	{
		if ($exc->getCode() == 403) {
			if (Config::contain('login_route')) {
				if (Config::find('login_route') != $controller->route) {
					$controller->redirect('/' . Config::find('login_route'));
				}
			}
		} else {
			$path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'others' . 
				DIRECTORY_SEPARATOR . 'error.php';
			print($controller->outputBuffering($path, array(
				'exception' => $exc,
			)));
		}
	}

	public static function abbrPath($path)
	{
		if (preg_match('/^' . str_replace('/', '\\/',
			str_replace('\\', '\\\\', Config::find('sys_src_path') . DIRECTORY_SEPARATOR)) . '(.*)$/', $path, $match)) {
			return $match[1];
		} else if (preg_match('/^' . str_replace('/', '\\/',
			str_replace('\\', '\\\\', Config::find('base_path') . DIRECTORY_SEPARATOR)) . '(.*)$/', $path, $match)) {
			return $match[1];
		}else {
			return $path;
		}
	}
}