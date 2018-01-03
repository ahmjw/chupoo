<?php

/**
 * Jakarta, February 29th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Chupoo\Views;

use Chupoo\Starter;
use Chupoo\Helpers\Config;

class LayoutCompiler extends Compiler
{
	public static $theme;
	public static $layout;
	public static $module_path;
	public static $sections = array();
	private static $data = array();

	public static function setData($data)
	{
		self::$data = $data;
	}

	private static function loadModuleProperties()
	{
		$path = self::$module_path . DIRECTORY_SEPARATOR . 'properties.php';
		if (!file_exists($path)) {
			return array();
		}
		return include($path);
	}

	public function render()
	{
		// Load properties
		$properties = $this->loadModuleProperties();
		if (self::$theme === null) {
			if (isset($properties['theme']) && $properties['theme'] !== null)
				self::$theme = $properties['theme'];
			else
				self::$theme = 'standard';
		}
		if (self::$layout === null) {
			if (isset($properties['layout']) && $properties['layout'] !== null)
				self::$layout = $properties['layout'];
			else
				self::$layout = 'index';
		}

		$this->dir = Config::find('theme_path') . DIRECTORY_SEPARATOR . self::$theme .
    		DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR;
		$this->path = $this->dir . self::$layout . '.php';
    	if (!file_exists($this->path)) {
        	throw new \Exception('Layout file is not found: ' . Starter::abbrPath($this->path), 500);
        }
        $content = $this->bind($this->path);
		// CSS
		$css = '<style type="text/css">' . $this->yields('css') . '</style>';
		$content = preg_replace_callback('/\<\/head\>/', function($match) use($css) {
			return $css . '</head>';
		}, $content);
		// JS
		$js = '<script language="javascript">' . $this->yields('js') . '</script>';
		$content = preg_replace_callback('/\<\/body\>/', function($match) use($js) {
			return $js . '</body>';
		}, $content);

		return $content;
	}

	private static function parseToken($token)
	{
		list($id, $content) = $token;
		if ($id == T_INLINE_HTML) {
			$content = Parser::parseLayout($content);
		}
		return $content;
	}

	public function includes($name)
	{
		$path = $this->dir . $name . '.php';
		if (!file_exists($path)) {
        	throw new \Exception('Layout file is not found: ' . Starter::abbrPath($path), 500);
        }
        return self::bind($path);
	}

	public function yields($name)
	{
		if (!isset(self::$sections[$name]))
			return;
		return self::$sections[$name];
	}

	public function widget($name)
	{
		Starter::getController()->widget($name);
	}

	public function import($name)
	{
		Starter::getController()->import($name);
	}
}