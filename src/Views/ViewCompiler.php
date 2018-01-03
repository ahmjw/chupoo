<?php

/**
 * Jakarta, February 28th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Chupoo\Views;

use Chupoo\Starter;
use Chupoo\Helpers\Config;

class ViewCompiler extends Compiler
{
	public $theme = 'standard';
	public $layout = 'index';
	private static $data = array();
	private static $instance;
	private $current_section;

	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function getPath($name, $module = null)
	{
		$controller = Starter::getController();
		$module = $module != null ? $module : $controller->normalizePath($controller->hmvc->module);
		if (preg_match('/^([a-zA-Z0-9_\/]+)\/([a-zA-Z0-9_]+)$/', $name, $matches)) {
			$module = $controller->normalizePath($matches[1]);
			$name = $matches[2];
		}
		return $controller->hmvc->module_path . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $name . '.php';
	}

	public function render(View $view)
	{
		$path = $this->getPath($view->name);
		if (!file_exists($path)) {
        	throw new \Exception('View file is not found: ' . Starter::abbrPath($path), 500);
        }
	    
	    self::$data = $view->data;
		$content = $this->bind($path, $view->data);
	}

	public function readView(View $view, $module = null)
	{
		$path = $this->getPath($view->name, $module);
		if (!file_exists($path)) {
        	throw new \Exception('View file is not found: ' . Starter::abbrPath($path), 500);
        }
		$a = $this->bind($path, $view->data);
	}

	public function appendSection($name, $content)
	{
		if (!isset(self::$sections[$name])) {
			self::$sections[$name] = $content;
		} else {
			self::$sections[$name] .= $content;
		}
	}

	public static function getData()
	{
		return self::$data;
	}

	public function includes($name, array $data = array())
	{
		self::$data = array_merge(self::$data, $data);
		$path = $this->getPath($name);
		if (!file_exists($path)) {
			throw new \Exception('View file is not found: ' . Starter::abbrPath($path), 500);
		}
		$content = $this->bind($path, self::$data);
		if (!empty(self::$sections)) {
			foreach (self::$sections as $key => $value) {
				if ($key != 'content') {
					$this->appendSection($key, $value);
				}
			}
		}
		return $content;
	}

	public function beginSection($name)
	{
		$this->current_section = $name;
		ob_start();
	}

	public function endSection()
	{
		$content = ob_get_clean();
		if (empty(LayoutCompiler::$sections[$this->current_section])) {
			LayoutCompiler::$sections[$this->current_section] = $content;
		} else {
			LayoutCompiler::$sections[$this->current_section] .= $content;
		}
		$this->current_section = null;
	}
}