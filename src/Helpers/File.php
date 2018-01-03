<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Chupoo\Helpers;

use Exception;

class File
{
	private $num_member = 0;
	private $is_multi;
    private $has_temp = false;
	private $multipart = array();
    public $extension;
    public $tmp_name;
    public $size;
    public $error;

    public function hasTemp()
    {
        return $this->has_temp;
    }

	public function loadTemp($name, $is_multi = false)
	{
		$this->is_multi = $is_multi;
		if ($is_multi === false) {
			if (!isset($_FILES[$name]) || empty($_FILES[$name]['name'])) {
				return false;
			}
			foreach ($_FILES[$name] as $key => $value) {
				$this->{$key} = $value;
			}
			$this->file_info = pathinfo($this->name);
			$this->extension = strtolower($this->file_info['extension']);
            $this->has_temp = true;
		} else {
			if (!isset($_FILES[$name])) {
				return false;
			}
            if (!empty($_FILES[$name])) {
    			$this->readPart($name, 'name');
    			$this->readPart($name, 'type');
    			$this->readPart($name, 'tmp_name');
    			$this->readPart($name, 'error');
    			$this->readPart($name, 'size');
    			$i = 0;
    			foreach ($_FILES[$name]['name'] as $key => $value) {
    				$this->multipart[$i]['file_info'] = pathinfo($value);
    				$this->multipart[$i]['extension'] = $this->multipart[$i]['file_info'];
    				$i++;
    			}
                $this->has_temp = true;
            }
		}
		return $this;
	}

	public function getMultipart()
	{
		return $this->multipart;
	}

	private function readPart($name, $field)
	{
		$i = 0;
		foreach ($_FILES[$name][$field] as $key => $value) {
			$this->multipart[$i][$field] = $value;
			$i++;
		}
		if ($this->num_member == 0) {
			$this->num_member = $i;
		}
	}

	public function upload($path)
	{
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		move_uploaded_file($this->tmp_name, $path);
	}

	public function multiUpload(array $paths)
	{
		$i = 0;
		foreach ($this->multipart as $key => $file) {
			move_uploaded_file($file['tmp_name'], $paths[$i]);
			$i++;
		}
	}

    public function download($path, $mime_type = 'plain/text')
    {
        header('Content-type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        readfile($path);
    }

	public function getFiles($path, $is_full_path = false, $recursively = false)
    {
        $files = array();
        if (!file_exists($path)) {
            throw new Exception("Path is not found: $path", 500);
        }
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $realpath = $path . DIRECTORY_SEPARATOR . $entry;
                    if(is_dir($realpath)) {
                        if($recursively === true) {
                            $files = $this->getFiles($realpath, $is_full_path, $recursively);
                        } else {
                            $this->getFiles($realpath, $is_full_path, $recursively);
                        }
                    } else {
                        if($is_full_path === true) {
                            $files[] = $realpath;
                        } else {
                            $files[] = $entry;
                        }
                    }
                }
            }
            closedir($handle);
        }
        return $files;
    }

    public function getDirs($path, $is_full_path = false, $recursively = false)
    {
        $dirs = array();
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $realpath = $path . DIRECTORY_SEPARATOR . $entry;
                    if(is_dir($realpath)) {
                        // Jika pembacaan hendak dilakukan secara rekursif
                        if($recursively === true) {
                            $dirs = $this->getDirs($realpath, $is_full_path, $recursively);
                        } else {
                            if($is_full_path === true) {
                                $dirs[] = $realpath;
                            } else {
                                $dirs[] = $entry;
                            }
                        }
                    }
                }
            }
            closedir($handle);
        }
        return $dirs;
    }

    public function getContents($path, array $args = array(), $recursively = false)
    {
    	$contents = array();
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $realpath = $path . DIRECTORY_SEPARATOR . $entry;
                    if(is_dir($realpath) && $recursively === true) {
                        $contents = $this->getFiles($realpath, $args, $recursively);
                    }
                    $contents[] = array(
                        'path' => $realpath,
                        'name' => $entry,
                        'type' => (is_file($realpath) ? 'File' : 'Directory'),
                    );
                }
            }
            closedir($handle);
        }
        return $contents;
    }

    public function makeDir($path)
    {
        $real_path = String::getInstance()->realPath($path);
        $dir = Config::getInstance()->getData('dir');
        $real_path = str_replace($dir, null, $real_path);
        foreach (explode(DS, $real_path) as $path) {
            if(!empty($path)) {
                $temp .= DS.$path;
                $scanned = $dir.$temp;
                if(!file_exists($scanned)) {
                    mkdir($scanned);
                }
            }
        }
    }

    public function deleteDir($path)
    {
        $path = rtrim(rtrim($path, '/'), DIRECTORY_SEPARATOR);
        $dir = opendir($path);
        while(false !== ( $entry = readdir($dir)) ) { 
            if (( $entry != '.' ) && ( $entry != '..' )) { 
                $realpath = $path . DIRECTORY_SEPARATOR . $entry;
                if ( is_dir($realpath) ) { 
                    $this->removeDir($realpath);
                } else { 
                    unlink($realpath);
                }
            } 
        } 
        closedir($dir);
        rmdir($path);
    }

    public function copyDir($src, $dst)
    {
        $dir = opendir($src);
        if (!file_exists($dst)) {
            mkdir($dst);
        }
        while(false !== ( $file = readdir($dir)) ) { 
            if (( $file != '.' ) && ( $file != '..' )) { 
                $src_path = $this->realPath($src . '/' . $file);
                $dst_path = $this->realPath($dst . '/' . $file);

                if ( is_dir($src_path) ) { 
                    $this->copyDir($src_path, $dst_path);
                } else { 
                    copy($src_path, $dst_path);
                }
            } 
        } 
        closedir($dir);
    }

    public function compress($source, $destination, $include_dir = false)
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }

        $zip = new \ZipArchive();
        if (!$zip->open($destination, \ZipArchive::CREATE)) {
            return false;
        }

        if (is_dir($source) === true) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file) {

                // Ignore "." and ".." folders
                if( in_array(substr($file, strrpos($file, DIRECTORY_SEPARATOR)+1), array('.', '..')) )
                    continue;

                $file = realpath($file);

                $name = str_replace($source . DIRECTORY_SEPARATOR, '', $file);
                if (is_dir($file) === true) {
                    $zip->addEmptyDir($name);
                } else if (is_file($file) === true) {
                    $zip->addFromString($name, file_get_contents($file));
                }
            }
        } else if (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        return $zip->close();
    }
}