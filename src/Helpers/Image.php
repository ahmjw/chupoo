<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Chupoo\Helpers;

class Image
{
    public $width = 0;
    public $height = 0;
    public $path;

    public function read($path = null)
    {
        $this->path = $path;
        $info = getimagesize($path);
        $this->width = $info[0];
        $this->height = $info[1];
    }

    public function cropRatio($source, $destination, $width, $height, $size, $x, $y)
    {
        $info = getimagesize($source);
        $this->width = $info[0];
        $this->height = $info[1];
        if($this->width < $size) return;
        $size = $size * $this->width / $width;
        $new_image = imagecreatetruecolor($size, $size);

        switch ($info['mime']) {
            case 'image/png':
                imagealphablending($new_image, false);
                imagesavealpha($new_image, true);
                $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                imagefilledrectangle($new_image, 0, 0, $size, $size, $transparent);
                $image = imagecreatefrompng($source);
                imagesavealpha($image, true);
                break;
            default:
                $image = imagecreatefromjpeg($source);
        }

        $x = $this->width * $x / $width;
        $y = $this->height * $y / $height;
        imagecopyresampled($new_image, $image, 0, 0, $x, $y, $size, $size, $size, $size);

        switch ($info['mime']) {
            case 'image/png':
                imagepng($new_image, $destination);
                break;
            default:
                imagejpeg($new_image, $destination, 100);
        }
    }

    public function resizeJpegFromUrl($size, $source, $destination)
    {
        ob_start();
        $img = file_get_contents($source);
        $im = imagecreatefromstring($img);
        $width = imagesx($im);
        $height = imagesy($im);
        $newwidth = $size;
        $newheight = ($height / $width) * $size;
        $thumb = imagecreatetruecolor($newwidth, $newheight);
        imagecopyresized($thumb, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        imagejpeg($thumb, $destination);
        ob_clean();
    }

    public function resize($size, $source, $destination)
    {
        $info = getimagesize($source);
        $this->width = $info[0];
        $this->height = $info[1];
        if($this->width < $size) return;

        $new_width = $size;
        $new_height = ($this->height / $this->width) * $size;
        $new_image = imagecreatetruecolor($new_width, $new_height);

        switch ($info['mime']) {
            case 'image/png':
                imagealphablending($new_image, false);
                imagesavealpha($new_image, true);
                $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
                imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
                $image = imagecreatefrompng($source);
                imagesavealpha($image, true);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source);
            default:
                $image = imagecreatefromjpeg($source);
        }
        
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $this->width, $this->height);

        switch ($info['mime']) {
            case 'image/png':
                imagepng($new_image, $destination);
                break;
            case 'image/gif':
                imagegif($new_image, $destination);
                break;
            default:
                imagejpeg($new_image, $destination, 100);
        }
        imagedestroy($image);
    }

    public function saveAs($dest)
    {
        $info = pathinfo($dest);
        if ($info['extension'] == 'png') {
            $image = imagecreatefromgif($this->path);
            imagepng($image, $dest);
            imagedestroy($image);
        }
    }

    public function trim($img, $color = 0xFFFFFF)
    {
        //find the size of the borders
        $b_top = 0;
        $b_btm = 0;
        $b_lft = 0;
        $b_rt = 0;

        //top
        for(; $b_top < imagesy($img); ++$b_top) {
          for($x = 0; $x < imagesx($img); ++$x) {
            if(imagecolorat($img, $x, $b_top) != $color) {
               break 2; //out of the 'top' loop
            }
          }
        }

        //bottom
        for(; $b_btm < imagesy($img); ++$b_btm) {
          for($x = 0; $x < imagesx($img); ++$x) {
            if(imagecolorat($img, $x, imagesy($img) - $b_btm-1) != $color) {
               break 2; //out of the 'bottom' loop
            }
          }
        }

        //left
        for(; $b_lft < imagesx($img); ++$b_lft) {
          for($y = 0; $y < imagesy($img); ++$y) {
            if(imagecolorat($img, $b_lft, $y) != $color) {
               break 2; //out of the 'left' loop
            }
          }
        }

        //right
        for(; $b_rt < imagesx($img); ++$b_rt) {
          for($y = 0; $y < imagesy($img); ++$y) {
            if(imagecolorat($img, imagesx($img) - $b_rt-1, $y) != $color) {
               break 2; //out of the 'right' loop
            }
          }
        }

        //copy the contents, excluding the border
        $newimg = imagecreatetruecolor(
            imagesx($img)-($b_lft+$b_rt), imagesy($img)-($b_top+$b_btm));

        imagecopy($newimg, $img, 0, 0, $b_lft, $b_top, imagesx($newimg), imagesy($newimg));
        return $newimg;
    }

    public function toString($image)
    {
        ob_start();
        imagepng($image);
        $contents =  ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    public function toBase64($data, $type)
    {
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    public function drawString($text, $width, $height, $font_size)
    {
        $font = "assets/fonts/NotoSansCJKjp-Regular.otf";
        $image=imagecreate($width, $height);
        $bg = imagecolorallocate($image, 0, 0, 0);
        $fg = imagecolorallocate($image, 255, 255, 255);
        $bbox = imagettfbbox($font_size, 0, $font, $text);
        $center1 = (imagesx($image) / 2) - (($bbox[2] - $bbox[0]) / 2);
        $center2 = (imagesy($image) / 2) - (($bbox[7] - $bbox[1]) / 2);
        imagettftext($image, $font_size, 0, $center1, $center2, $fg, $font, $text);
        return $image;
    }

    public function resizePng($source, $size = 64)
    {
        $width = imagesx($source);
        $height = imagesy($source);

        $new_width = $size;
        $new_height = $size;
        $new_image = imagecreatetruecolor($new_width, $new_height);

        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        imagesavealpha($source, true);
        
        imagecopyresampled($new_image, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        return $new_image;
    }
}