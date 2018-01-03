<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Chupoo\Helpers;

class UndefinedClass
{
	public function __get($name)
	{
		return null;
	}
}