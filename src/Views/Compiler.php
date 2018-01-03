<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Djokka\Views;

use Djokka\Starter;
use Djokka\Helpers\Config;

class Compiler
{
	public $path;
	public $user;

	public function __construct()
	{
		$controller = Starter::getController();
		foreach (Config::find('properties') as $key => $value) {
			$this->{$key} = $value;
		}
		$this->action = $controller->action;
		$this->module = $controller->module;
		$this->route = $controller->route;
		$this->user = $controller->user;
	}

	public function bind($path, array $data = array())
	{
		ob_start();
		if (!empty($data)) extract($data);
		include($path);
		return ob_get_clean();
	}

	public function css($url, $is_asset = false)
	{
		$base_url = !$is_asset ? $this->themeUrl() : $this->assetUrl();
		$url = $base_url . $url;
		return '<link rel="stylesheet" type="text/css" href="' . $url . '" />';
	}

	public function js($url, $is_asset = false)
	{
		$base_url = !$is_asset ? $this->themeUrl() : $this->assetUrl();
		$url = $base_url . $url;
		return '<script languange="javascript" src="' . $url . '"></script>';
	}

	public function link($label, $url, $attrs = '')
	{
		return '<a href="' . $this->url($url) . '"' . $this->renderAttr($attrs) . '>' . $label . '</a>';
	}

	public function emailLink($label, $url, $attrs = '')
	{
		return '<a href="mailto:' . $url . '"'.$this->renderAttr($attrs).'>' . $label . '</a>';
	}

	public function imageLink($alt, $image_url, $url, $attrs = '')
	{
		return '<a href="' . Starter::getController()->url($url) . '"'.$this->renderAttr($attrs).' title="'.$alt.'">' .
			'<img src="' . $image_url . '" alt="' . $alt . '" /></a>';
	}

	public function comboBox($name, $selected_val, array $items, $attrs = '')
	{
		$html = '<select name="' . $name . '"' . $this->renderAttr($attrs) . '>';
		if (!empty($items)) {
			foreach ($items as $key => $value) {
				$selected = $key == $selected_val ? ' selected="selected"' : '';
				$html .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
			}
		}
		$html .= '</select>';
		return $html;
	}

	public function radioButton($name, $value, $selected_val, $attrs = '')
	{
		$html = '<input type="radio" name="' . $name . '" value="'.$value.'"';
		$html .= $value == $selected_val ? ' checked="checked"' : '';
		$html .= $this->renderAttr($attrs) . ' />';
		return $html;
	}

	public function renderAttr($attrs)
	{
		if (is_array($attrs)) {
			$str = '';
			foreach ($attrs as $key => $value) {
				$str .= ' ' . $key . '="' . $value . '"';
			}
			return $str;
		}
		return $attrs;
	}

	public function data($name, array $data = array())
	{
		$path = $this->themeDir() . 'data' . DIRECTORY_SEPARATOR . $name . '.php';
		if (!file_exists($path)) {
			throw new \Exception('Data file is not found: ' . Starter::abbrPath($path), 500);
		}
		$data = Starter::getController()->outputBuffering($path, $data);
		if (is_string($data)) {
			return $data;
		}
		return $data;
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

	public function url($route = null)
	{
		if ($route !== null) {
			if (preg_match('/^\/(.*?)$/', $route, $match)) {
				return Config::find('base_url') . '/' . $match[1];
			} else {
				return Config::find('base_url') . '/' . Config::find('module') . '/' . $route;
			}
		} else {
			return Config::find('base_url') . '/' . Starter::getController()->hmvc->initial_route;
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
		return Config::find('theme_url') . '/themes/' . LayoutCompiler::$theme . '/' . $url;
	}

	public function themeDir($path = '')
	{
		$path = str_replace('\\', DIRECTORY_SEPARATOR, str_replace('/', DIRECTORY_SEPARATOR, $path));
		return Config::find('base_path') . DIRECTORY_SEPARATOR. 'themes' . DIRECTORY_SEPARATOR . LayoutCompiler::$theme . 
			DIRECTORY_SEPARATOR . $path;
	}
}