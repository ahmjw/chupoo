<?php

/**
 * Jakarta, January 29th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Chupoo\Views;

use Chupoo\Config;
use Chupoo\Starter;

class View
{
	public $name;
	public $data = array();

	public function __construct($name, array $data = array())
	{
		$this->name = $name;
		$this->data = $data;
	}
}