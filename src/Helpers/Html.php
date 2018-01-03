<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Chupoo\Helpers;

class Html
{
	public function link($label, $url, $attrs = '')
	{
		return '<a href="' . Starter::getController()->link($url) . '"'.$this->renderAttr($attrs).'>' . $label . '</a>';
	}

	public function emailLink($label, $url, $attrs = '')
	{
		return '<a href="mailto:' . $url . '"'.$this->renderAttr($attrs).'>' . $label . '</a>';
	}

	public function imageLink($alt, $image_url, $url, $attrs = '')
	{
		return '<a href="' . Starter::getController()->link($url) . '"'.$this->renderAttr($attrs).' title="'.$alt.'">' .
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
}