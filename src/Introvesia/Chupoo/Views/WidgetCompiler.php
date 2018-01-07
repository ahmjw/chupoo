<?php

/**
 * Jakarta, February 28th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Introvesia\Chupoo\Views;

use Introvesia\Chupoo\Starter;
use Introvesia\Chupoo\Helpers\Config;

class WidgetCompiler extends Compiler
{
	private static $data = array();
	private $sections = array();
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

	public function render(View $view, $module = null)
	{
		$path = $this->getPath($view->name, $module);
		if (!file_exists($path)) {
        	throw new \Exception('View file is not found: ' . Starter::abbrPath($path), 500);
        }
		$this->bind($path, $view->data);
		$sections = $this->getSection();
		return $sections['content'];
	}

	public function getSection($name = null)
	{
		if ($name === null) {
			return $this->sections;
		}
		return isset($this->sections[$name]) ? $this->sections[$name] : '';
	}

	public function appendSection($name, $content)
	{
		if (!isset($this->sections[$name])) {
			$this->sections[$name] = $content;
		} else {
			$this->sections[$name] .= $content;
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
		if (!empty($this->sections)) {
			foreach ($this->sections as $key => $value) {
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
		if ($this->current_section == 'content') {
			if (empty($this->sections[$this->current_section])) {
				$this->sections[$this->current_section] = $content;
			} else {
				$this->sections[$this->current_section] .= $content;
			}
		} else {
			if (empty(LayoutCompiler::$sections[$this->current_section])) {
				LayoutCompiler::$sections[$this->current_section] = $content;
			} else {
				LayoutCompiler::$sections[$this->current_section] .= $content;
			}
		}
		$this->current_section = null;
	}
}