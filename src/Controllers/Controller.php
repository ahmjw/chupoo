<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Chupoo\Controllers;

use Exception;
use Chupoo\Starter;
use Chupoo\Views\View;
use Chupoo\Views\ViewCompiler;
use Chupoo\Views\LayoutCompiler;
use Chupoo\Views\WidgetCompiler;
use Chupoo\Helpers\Config;
use Chupoo\Helpers\User;
use Chupoo\Helpers\File;
use Chupoo\Helpers\Image;
use Chupoo\Helpers\Session;
use Chupoo\Helpers\Html;
use Chupoo\Helpers\StringLib;
use Chupoo\Helpers\Email;

class Controller
{
	public $content_type = 'html';
	public $hmvc;
	public $widgets = array();

	public function __construct()
	{
		$this->user = new User();
		$this->file = new File();
		$this->html = new Html();
		$this->session = Session::getInstance();
		$this->string = new StringLib();
		$this->email = new Email();
		$this->image = new Image();
	}

	public function hmvcLoad($route, $type = Hmvc::TYPE_PAGE)
	{
		// Tracing route
        $this->hmvc = new Hmvc($route, $type);
        Config::save('module', $this->hmvc->module);
        $this->module = $this->hmvc->module;
        Config::save('action', $this->hmvc->action);
        $this->action = $this->hmvc->action;
        Config::save('route', $this->hmvc->route);
        $this->route = $this->hmvc->route;
	}

	public function render($route, array $args = array(), $type = Hmvc::TYPE_PAGE)
	{
		$this->hmvcLoad($route, $type);
        // Preparation
        $constructed = $this->inheritLoader();
    	if ($this->hmvc->route != 'index/error') {
    		$this->aliasing();
    		Config::save('module', $this->hmvc->module);
	        $this->module = $this->hmvc->module;
	        Config::save('action', $this->hmvc->action);
	        $this->action = $this->hmvc->action;
	        Config::save('route', $this->hmvc->route);
	        $this->route = $this->hmvc->route;
    	}
    	if (!$constructed) {
    		$constructed = $this->inheritLoader();
    	}
        if (method_exists($this, 'afterRoute')) {
        	call_user_func(array($this, 'afterRoute'));
        }
        // Execute route
        if ($this->hmvc->status_code == 404) {
        	throw new Exception('Action file is not found: ' . Starter::abbrPath($this->hmvc->file_path), 404);
        }
        if ($this->hmvc->route != 'index/_error') {
	    	$allowed = true;
	    	if (isset($this->access)) {
	    		if (isset($this->access[$this->hmvc->action])) {
	    			$allowed = (bool)$this->access[$this->hmvc->action];
	    		} else {
	    			$allowed = isset($this->access[0]) ? (bool)$this->access[0] : true;
	    		}
	    	}
	    	if (!$allowed) {
	    		throw new Exception("Access denied", 403);
	    	}
	    }
    	
    	$this->content = $this->outputBuffering($this->hmvc->file_path);
    	
    	if ($this->content instanceof \Closure) {
    		$closure = $this->content;
    		$args = !empty($args) ? $args : $this->hmvc->args;
    		$closure = call_user_func_array($closure, $args);
	        if ($closure instanceof View) {
	        	$compiler = new ViewCompiler();
	        	$compiler->render($closure);
	        	$compiler = new LayoutCompiler();
	        	$compiler::$module_path = $this->hmvc->module_path;
	        	$compiler->render();
		    } else {
		    	header('Content-type: application/json');
	        	print(json_encode($closure));
		    }
		}
	}

	private function inheritLoader()
	{
    	$path = $this->hmvc->module_path . DIRECTORY_SEPARATOR . 'index.php';
    	if (file_exists($path)) {
    		include_once($path);
    		return true;
    	} else {
    		$routes = explode('/', $this->hmvc->module);
    		$count = count($routes);
    		if (!empty($routes)) {
	    		for ($i = 1; $i < $count; $i++) {
	    			$temp = $routes;
	    			$path = $this->hmvc->trace_dir . DIRECTORY_SEPARATOR . implode(array_splice($temp, 0, -$i), DIRECTORY_SEPARATOR .
	    				'modules' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
	    			if (file_exists($path)) {
	    				include_once($path);
	    				return true;
	    			}
	    		}
			}
    	}
    	return false;
	}

	private function aliasing()
	{
		$routes = isset($this->alias) ? $this->alias : Config::find('alias');
		if ($routes === null) return;
		if (empty($this->hmvc->args)) {
			$this->hmvc->args = array();
		}
		foreach ($routes as $route => $pattern) {
            $pattern = preg_replace_callback('/\((.*?)\)/i', function($matches) {
                return '('.$matches[1].')';
            }, $pattern);
            $pattern = '/'.str_replace('/', '\/', $pattern).'/i';
            if (preg_match($pattern, Config::find('uri'), $match)) {
                $values = array_slice($match, 1);
                $this->hmvc->args = array();
                foreach ($values as $i => $value) {
                    $this->hmvc->args[] = $value;
                }
                if (is_numeric(strpos($route, '/'))) {
	                $this->hmvc->module = substr($route, 0, strrpos($route, '/'));
	                $this->hmvc->action = substr($route, strrpos($route, '/') + 1);
	            } else {
	            	$this->hmvc->action = $route;
	            }
                $this->hmvc->route = $route;
                $this->hmvc->module_path = $this->hmvc->trace_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR, $this->hmvc->module);
                $this->hmvc->file_path = $this->hmvc->module_path . DIRECTORY_SEPARATOR. 'controllers' . DIRECTORY_SEPARATOR . $this->hmvc->action . '.php';
                if (file_exists($this->hmvc->file_path)) {
                	$this->hmvc->status_code = 200;
                }
                break;
            }
        }
		return $this->hmvc->args;
	}

	public function plugin($name)
	{
		$name = str_replace('/', DIRECTORY_SEPARATOR, $name);
		$path = Config::find('base_path') . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $name . '.php';
		if (!file_exists($path)) {
			throw new Exception('Plugin file is not found: ' . Starter::abbrPath($path), 500);
		}
		require_once($path);
	}

	public function data($name, array $args = array())
	{
		if (preg_match('/^\/(?:(.*?)\/)?(.*?)$/', $name, $match)) {
			$name = $match[2];
			$module = preg_replace('/\//', DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR, $match[1]);
		} else {
			$module = preg_replace('/\//', DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR, $this->hmvc->module);
		}
		$path = Config::find('app_path') . DIRECTORY_SEPARATOR . 'modules' .
			DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $name;
		if (!file_exists($path)) {
			throw new Exception('Data file is not found: ' . Starter::abbrPath($path), 500);
		}
		return $this->outputBuffering($path, $args);
	}

	public function dataPath($name)
	{
		if (preg_match('/^\/(.*?)$/', $name, $match)) {
			$name = $match[1];
			$path = Config::find('app_path') . DIRECTORY_SEPARATOR . 'data' .
				DIRECTORY_SEPARATOR . $name;
		} else {
			$module = preg_replace('/\//', DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR, $this->hmvc->module);
			$path = Config::find('app_path') . DIRECTORY_SEPARATOR . 'modules' .
				DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $name;
		}
		return $path;
	}

	public function header()
	{
		switch (func_num_args()) {
			case 1:
				$items = func_get_arg(0);
				if (!is_array($items)) {
					throw new Exception("The argument is not array", 500);
				}
				foreach ($items as $name => $value) {
					header($name . ': ' . $value);
				}
				break;
			case 2:
				header(func_get_arg(0) . ': ' . func_get_arg(1));
				break;
			default:
				throw new Exception("Bad argument", 500);
		}
	}

	public function hasArg($index = null)
	{
		if ($index === null) {
			return isset($this->hmvc->args) && !empty($this->hmvc->args);
		} else {
			return isset($this->hmvc->args[$index]);
		}
	}

	public function view()
	{
		switch (func_num_args()) {
			case 0:
				$name = $this->action;
				$args = array();
				break;
			case 1:
				$arg = func_get_arg(0);
				if (!is_array($arg)) {
					$name = $arg;
					$args = array();
				} else {
					$name = $this->action;
					$args = $arg;
				}
				break;
			case 2:
				$name = func_get_arg(0);
				$args = func_get_arg(1);
				break;
			default:
				throw new Exception("Invalid argument", 500);
		}
		return new View($name, $args);
	}

	public function getViewPath($name)
	{
		$path = Config::find('theme_path') . DIRECTORY_SEPARATOR . $this->theme .
	    	DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->normalizePath($this->hmvc->module) . DIRECTORY_SEPARATOR . $name . '.php';
        return $path;
	}

	public function model($name, $is_new = false)
	{
		if (preg_match('/^\/(.*?)$/', $name, $match)) {
			$name = $match[1];
			$module = preg_replace('/\//', DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR, $this->hmvc->module);
			$path = Config::find('app_path') . DIRECTORY_SEPARATOR . 'modules' .
				DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $name . '.php';
		} else {
			$name = str_replace('/', '\\', $name);
			$path = Config::find('app_path') . DIRECTORY_SEPARATOR . 'models' .
				DIRECTORY_SEPARATOR . $name . '.php';
		}
		$path = $this->normalizePath($path);
		if (!file_exists($path)) {
			throw new Exception('Model file is not found: ' . Starter::abbrPath($path), 500);
		}
		include_once($path);
		$name = 'Models\\' . $name;
		if (!class_exists($name)) {
			throw new Exception("Model class is not found: $name", 500);
		}
		$is_new = (bool)$is_new;
		$obj = new $name;
		$obj->dataset('controller', $this);
		$obj->dataset('is_new', $is_new);
		$obj->dataset('is_init_new', $is_new);
		return $obj;
	}

	public function getConfig($filename)
	{
		$path = Config::find('app_path') . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $filename . '.php';
		if (!file_exists($path)) {
			throw new Exception('Config file not found: ' . $filename . '.php', 500);
		}
		return include($path);
	}

	public function config()
	{
		switch (func_num_args()) {
			case 0:
				return Config::findAll();
			case 1:
				return Config::find(func_get_arg(0));
			case 2:
				Config::save(func_get_arg(0), func_get_arg(1));
				break;
			default:
				throw new Exception("Invalid argument", 500);
		}
	}

	public function session()
	{
		switch (func_num_args()) {
			case 0:
				return $this->session->findAll();
			case 1:
				return $this->session->find(func_get_arg(0));
			case 2:
				$this->session->save(func_get_arg(0), func_get_arg(1));
				break;
			default:
				throw new Exception("Invalid argument", 500);
		}
	}

	public function user()
	{
		switch (func_num_args()) {
			case 0:
				return $this->user->find(Config::find('default_user_session_name'));
			case 1:
				return $this->user->find(func_get_arg(0));
			default:
				throw new Exception("Invalid argument", 500);
		}
	}

	public function url($route = false)
	{
		if ($route) {
			if (preg_match('/^\/(.*?)$/', $route, $match)) {
				return Config::find('base_url') . '/' . $match[1];
			} else {
				return Config::find('base_url') . '/' . Config::find('module') . '/' . $route;
			}
		} else {
			return Config::find('base_url') . '/' . $this->hmvc->initial_route;
		}
	}

	public function assetUrl($url = '')
	{
		return Config::find('base_url') . '/assets/' . $url;
	}

	public function assetDir($path = '')
	{
		return Config::find('base_path') . DIRECTORY_SEPARATOR. 'assets' . DIRECTORY_SEPARATOR . $path;
	}

	public function themeUrl($url = '')
	{
		return Config::find('base_url') . '/themes/' . $this->theme . '/' . $url;
	}

	public function themeDir($path = '')
	{
		$path = str_replace('\\', DIRECTORY_SEPARATOR, str_replace('/', DIRECTORY_SEPARATOR, $path));
		return Config::find('base_path') . DIRECTORY_SEPARATOR. 'themes' . DIRECTORY_SEPARATOR . $this->theme . DIRECTORY_SEPARATOR . $path;
	}

	public function redirect($route)
	{
		$this->session->save('http_referer', $this->route);
		header('Location: ' . $this->url($route));
		exit();
	}

	public function normalizePath($path)
	{
		return str_replace('\\', DIRECTORY_SEPARATOR, str_replace('/', DIRECTORY_SEPARATOR, $path));
	}

	// Source: http://stackoverflow.com/questions/6225351/how-to-minify-php-page-html-output
	public function sanitizeOutput($buffer)
	{
	    $search = array(
	        '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
	        '/[^\S ]+\</s',  // strip whitespaces before tags, except space
	        '/(\s)+/s'       // shorten multiple whitespace sequences
	    );

	    $replace = array(
	        '>',
	        '<',
	        '\\1'
	    );

	    $buffer = preg_replace($search, $replace, $buffer);

	    return $buffer;
	}

	public function outputBuffering($path, array $args = array())
	{
		if (Config::find('compress_script')) {
			ob_start(array($this, 'sanitizeOutput'));
		} else {
			ob_start();
		}
		extract($args, EXTR_PREFIX_SAME, 'djokka_');
		$content = include($path);
		if ($content !== 1) {
			return $content;
		}
		return ob_get_clean();
	}

	public function widget()
	{
		switch (func_num_args()) {
			case 1:
				$key = func_get_arg(0);
				if (isset($this->widgets[$key])) {
					print_r($this->widgets[$key]);
				}
				break;
			case 2:
				$key = func_get_arg(0);
				$widget = func_get_arg(1);
				$module = str_replace('/', DIRECTORY_SEPARATOR, substr($widget, 0, strrpos($widget, '/')));
				$action = substr($widget, strrpos($widget, '/') + 1);

				$path = Config::find('module_path') . DIRECTORY_SEPARATOR . str_replace(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . 'modules' .
					DIRECTORY_SEPARATOR, $module) . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . '_' . $action . '.php';
				if (file_exists($path)) {
					$closure = $this->outputBuffering($path);
		    		$args = !empty($args) ? $args : $this->hmvc->args;
		    		$closure = call_user_func_array($closure, $args);
			        if ($closure instanceof View) {
			        	$compiler = new WidgetCompiler();
			        	if (!isset($this->widgets[$key]))
			        		$this->widgets[$key] = '';
			        	$this->widgets[$key] .= $compiler->render($closure, $module);
				    }
				} else {
					throw new Exception('The widget is not found: ' . Starter::abbrPath($path), 500);
				}
				break;
			default:
				throw new Exception('Bad argument', 500);
		}
	}

	public function import($route)
	{
		$module = str_replace('/', DIRECTORY_SEPARATOR, substr($route, 0, strrpos($route, '/')));
		$action = substr($route, strrpos($route, '/') + 1);
		$path = $this->moduleDir() . str_replace(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR, $module) .
			DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $action . '.php';
		if (file_exists($path)) {
			$content = $this->outputBuffering($path);
			if ($content instanceof View) {
				$compiler = new ViewCompiler();
				echo $compiler->readView($content);
			} else {
				echo $content;
			}
		} else {
			throw new Exception('The module is not found: ' . Starter::abbrPath($path), 500);
		}
	}

	public function clearWidget($name = null)
	{
		if ($name !== null) {
			if (isset($this->widgets[$name])) {
				unset($this->widgets[$name]);
			}
		} else {
			$this->widgets = array();
		}
	}

	public function getClientIp()
	{
	    $ipaddress = '';
	    if (isset($_SERVER['HTTP_CLIENT_IP']))
	        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    else if(isset($_SERVER['HTTP_X_FORWARDED']))
	        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
	        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	    else if(isset($_SERVER['HTTP_FORWARDED']))
	        $ipaddress = $_SERVER['HTTP_FORWARDED'];
	    else if(isset($_SERVER['REMOTE_ADDR']))
	        $ipaddress = $_SERVER['REMOTE_ADDR'];
	    else
	        $ipaddress = 'UNKNOWN';
	    return $ipaddress;
	}
}