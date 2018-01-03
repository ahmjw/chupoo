<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Chupoo\Controllers;

class CoreHelper
{
	public function getModel()
	{
		$path = Config::find('model_path');
		return $this->traceNamespace($path);
	}

	private function traceNamespace($path, $namespace = '')
	{
		$items = array();
		if ($handle = opendir($path)) {
	        while (false !== ($entry = readdir($handle))) {
	            if ($entry != "." && $entry != "..") {
	            	$current = $path . DIRECTORY_SEPARATOR . $entry;
	            	if(is_dir($current)) {
	            		$namespace .= ucfirst($entry) . '\\';
	            		return $this->traceNamespace($current, $namespace);
	            	} else {
	            		$items[] = $namespace . preg_replace('/^(.*?)\.php$/', '$1', $entry);
	            	}
	            }
	        }
	    }
	    return $items;
	}

	public function getController($path)
	{
		$items = array();
		if ($handle = opendir($path)) {
	        while (false !== ($entry = readdir($handle))) {
	            if ($entry != "." && $entry != "..") {
	            	$entry_path = $path . DIRECTORY_SEPARATOR . $entry;
	            	if (is_dir($entry_path)) {
	            		$trace_path = $path . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'modules';
	            		if (file_exists($trace_path)) {
	            			$items[$entry] = $this->getController($trace_path);
	            		} else {
	            			$trace_path = $path . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'controllers';
	            			if (file_exists($trace_path)) {
	            				$items[$entry] = $this->getController($trace_path);
	            			}
	            		}
	            	} else {
	            		$items[] = preg_replace('/^(.*?)\.php$/', '$1', $entry);
	            	}
	            }
	        }
	    }
	    return $items;
	}

	public function traceController($path, &$items, $module = '')
	{
		if ($handle = opendir($path)) {
			$parent_module = $module;
	        while (false !== ($entry = readdir($handle))) {
	            if ($entry != "." && $entry != "..") {
	            	$entry_path = $path . DIRECTORY_SEPARATOR . $entry;
	            	if (is_dir($entry_path)) {
	            		$trace_path = $path . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'modules';
	            		if (file_exists($trace_path)) {
	            			if (preg_match('/(^.*?application.modules.[A-Za-z0-9]+.)modules$/', $trace_path, $match)) {
		            			$path2 = $match[1] . 'controllers';
	            				if (file_exists($path2) && $handle2 = opendir($path2)) {
	            					while (false !== ($entry2 = readdir($handle2))) {
							            if ($entry2 != "." && $entry2 != "..") {
							            	$entry_path2 = $path2 . DIRECTORY_SEPARATOR . $entry2;
							            	if (is_file($entry_path2)) {
							            		$module2 = $module;
							            		$items[$entry][] = preg_replace('/^(.*?)\.php$/', '$1', $entry2);
							            	}
							            }
							        }
	            				}
	            			}
	            			$module = (!empty($parent_module) ? $parent_module  . '/' : '') . $entry;
	            			$this->traceController($trace_path, $items, $module);
	            			$module = $parent_module;
	            		} else {
	            			$trace_path = $path . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'controllers';
	            			if (file_exists($trace_path)) {
	            				$module .= ($parent_module == '' ? '' : '/') . $entry;
	            				$this->traceController($trace_path, $items, $module);
	            				$module = $parent_module;
	            			}
	            		}
	            	} else {
	            		$items[$module][] = preg_replace('/^(.*?)\.php$/', '$1', $entry);
	            	}
	            }
	        }
	    }
	}

	public function traceRoute($path, &$items, $module = '')
	{
		if ($handle = opendir($path)) {
			$parent_module = $module;
	        while (false !== ($entry = readdir($handle))) {
	            if ($entry != "." && $entry != "..") {
	            	$entry_path = $path . DIRECTORY_SEPARATOR . $entry;
	            	if (is_dir($entry_path)) {
	            		$trace_path = $path . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'modules';
	            		if (file_exists($trace_path)) {
	            			$module = $entry;
	            			$this->traceRoute($trace_path, $items, $module);
	            			$module = $parent_module;
	            		} else {
	            			$trace_path = $path . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR . 'controllers';
	            			if (file_exists($trace_path)) {
	            				$module .= ($parent_module == '' ? '' : '/') . $entry;
	            				$this->traceRoute($trace_path, $items, $module);
	            				$module = $parent_module;
	            			}
	            		}
	            	} else {
	            		$items[] = $module . '/' . preg_replace('/^(.*?)\.php$/', '$1', $entry);
	            	}
	            }
	        }
	    }
	}
}