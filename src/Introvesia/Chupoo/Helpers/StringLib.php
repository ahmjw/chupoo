<?php

namespace Introvesia\Chupoo\Helpers;

class StringLib
{
    public function urlParam(array $data, $from_first = true)
    {
        $param = '';
        $i = 0;
        foreach ($data as $key => $value) {
            if ($from_first && $i == 0) {
                $param .= '?' . $key . '=' . $value;
            } else {
                $param .= '&' . $key . '=' . $value;
            }
            $i++;
        }
        return $param;
    }

    /**
     * @source http://www.phpro.org/examples/Convert-Numbers-To-Roman-Numerals.html
     */
    public function toRoman($num) 
    {
        $n = intval($num);
        $res = '';
     
        /*** roman_numerals array  ***/
        $roman_numerals = array(
            'M'  => 1000,
            'CM' => 900,
            'D'  => 500,
            'CD' => 400,
            'C'  => 100,
            'XC' => 90,
            'L'  => 50,
            'XL' => 40,
            'X'  => 10,
            'IX' => 9,
            'V'  => 5,
            'IV' => 4,
            'I'  => 1
        );
 
        foreach ($roman_numerals as $roman => $number) 
        {
            /*** divide to get  matches ***/
            $matches = intval($n / $number);
     
            /*** assign the roman char * $matches ***/
            $res .= str_repeat($roman, $matches);
     
            /*** substract from the number ***/
            $n = $n % $number;
        }
 
        /*** return the res ***/
        return $res;
    }

	public function slugify($text)
    {
      return strtolower(trim(preg_replace('/\W+/', '-', $text), '-'));
    }

	/*public function getTimeAgo($datetime, $full = false)
    {
    	$now = new \DateTime;
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'tahun',
            'm' => 'bulan',
            'w' => 'minggu',
            'd' => 'hari',
            'h' => 'jam',
            'i' => 'menit',
            's' => 'detik',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v;// . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' yang lalu' : 'baru saja';
    }*/
}