<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Introvesia\Chupoo\Controllers;

use Introvesia\Chupoo\Helpers\Config;

class Hmvc
{
    public $module;
    public $action;
    public $module_path;
    public $module_url;
    public $file_path;
    public $trace_dir;
    public $route;
    public $initial_route;
    public $status_code = 404;
    public $args = array();
    public $type;
    private $ignore_widget = false;
    const TYPE_PAGE = 0;
    const TYPE_WIDGET = 1;
    
    public function __construct($route, $type)
    {
        $this->initial_route = $route;
        $this->ignore_widget = $type == self::TYPE_WIDGET;
        $this->trace_dir = Config::find('module_path');
        $this->module_url = Config::find('base_url');
        $this->trace();
    }

    public function ignoreWidget()
    {
        $this->ignore_widget = true;
    }

    private function trace()
    {
        $current = null;
        $previous = null;
        $routes = explode('/', $this->initial_route, Config::find('max_route_depth'));
        $count = count($routes);
        if (empty($routes[$count-1])) {
            unset($routes[$count-1]);
        }
        if ($count > 1) {
            for ($i = 0; $i < $count; $i++) {
                if ($i > 0) {
                    $current .= DIRECTORY_SEPARATOR;
                }
                if (!isset($routes[$i])) continue;
                $current .= $routes[$i];
                $this->module_path = $this->trace_dir . DIRECTORY_SEPARATOR .
                    str_replace(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR, $current);
                if (file_exists($this->module_path)) {
                    if (isset($routes[$i + 1])) {
                        $this->action = $routes[$i + 1];
                    } else {
                        $this->action = 'index';
                    }
                    $this->type = preg_match('/^[^_]/', $this->action) ? self::TYPE_PAGE : self::TYPE_WIDGET;
                    $this->file_path = $this->module_path . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $this->action . '.php';
                    $this->module = $current;
                    if (file_exists($this->file_path)) {
                        if (!$this->ignore_widget && $this->type == self::TYPE_WIDGET) {
                            throw new \Exception('Direct access to widget is not allowed', 403);
                        }
                        $this->status_code = 200;
                        $i++;
                        break;
                    }
                } else {
                    $this->module_path = $this->trace_dir . DIRECTORY_SEPARATOR .
                        str_replace(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR, $previous);
                    $this->module = $previous;
                    break;
                }
                if ($i > 0) {
                    $previous .= DIRECTORY_SEPARATOR;
                }
                $previous .= $routes[$i];
            }

            foreach (array_slice($routes, $i + 1) as $arg) {
                $this->args[] = urldecode($arg);
            }
        } else {
            $this->module = !empty($this->initial_route) ? $this->initial_route : 'index';
            $this->module_path = $this->trace_dir . DIRECTORY_SEPARATOR . $this->module;
            $this->action = 'index';
            $this->type = self::TYPE_PAGE;
            $this->file_path = $this->module_path . DIRECTORY_SEPARATOR. 'controllers' . DIRECTORY_SEPARATOR . $this->action . '.php';
        }
        if (file_exists($this->file_path)) {
            $this->status_code = 200;
        }
        $this->module = str_replace('\\', '/', $this->module);
        if ($this->module != '') {
            $this->route = $this->module . '/' . $this->action;
        } else {
            $this->route = $this->initial_route;
        }
        $this->module_url .= '/' . $this->module;
    }

    public function setData(array $data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function setRoute($route)
    {
        if (preg_match('/^([a-zA-Z0-9_\/]+)\/([a-zA-Z0-9_]+)$/', $route, $match)) {
            $this->module = $match[1];
            $this->action = $match[2];
        } else {
            $this->action = 'index';
        }
        $this->type = self::TYPE_PAGE;
        $this->route = $this->module . '/' . $this->action;
        $this->module_path = $this->trace_dir . DIRECTORY_SEPARATOR . 
            str_replace('/', DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR, $this->module);
        $this->file_path = $this->module_path . DIRECTORY_SEPARATOR. 'controllers' . DIRECTORY_SEPARATOR . $this->action . '.php';
        if (file_exists($this->file_path)) {
            $this->status_code = 200;
        } else {
            $this->status_code = 404;
        }
    }

    public function setAction($name)
    {
        $this->action = $name;
        $this->type = self::TYPE_PAGE;
        $this->file_path = $this->module_path . DIRECTORY_SEPARATOR. 'controllers' . DIRECTORY_SEPARATOR . $this->action . '.php';
        if (file_exists($this->file_path)) {
            $this->status_code = 200;
        } else {
            $this->status_code = 404;
        }
    }
}