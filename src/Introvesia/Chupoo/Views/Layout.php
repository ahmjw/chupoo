<?php

/**
 * Jakarta, February 29th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Introvesia\Chupoo\Views;

use Introvesia\Chupoo\Starter;
use Introvesia\Chupoo\Helpers\Config;
use Introvesia\PhpDomView\Layout as DomLayout;
use Introvesia\PhpDomView\Widget;

class Layout
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

	public function render($view)
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

		$this->dir = Config::find('theme_path') . DIRECTORY_SEPARATOR . self::$theme;
		$view_path = self::$module_path . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $view->name . '.html';

        $config = array(
        	'name' => self::$layout,
        	'path' => $this->dir,
        	'view' => $view_path,
        	'module_path' => self::$module_path,
        	'base_url' => Config::find('base_url'),
        	'layout_url' => Config::find('theme_url') . '/' . self::$theme
        );

        $dom = new DomLayout($config, $view->data);
        foreach ($dom->getWidgetKeys() as $widget_key) {
        	$widget_info = Starter::getController()->getWidgetInfo($widget_key);
        	$dom->widget($widget_key, $widget_info);
        }
        $dom->parse();
        print($dom->getOutput());
	}
}