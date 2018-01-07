<?php

/**
 * Jakarta, January 17th 2016
 * @link http://www.introvesia.com
 * @author Ahmad <ahmadjawahirabd@gmail.com>
 */

namespace Introvesia\Chupoo\Helpers;

class Email
{
	public function send($from, $to, $subject, $message, $headers = array())
    {
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=iso-8859-1';
        $headers[] = 'From: {$from}';
        $headers[] = 'Subject: {$subject}';
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        if(is_array($to)) {
            $to = implode(',', $to);
        }
        if($success = mail($to, $subject, $message, implode('\r\n', $headers))) {
            return $success;
        } else {
            throw new \Exception('Failed to send e-mail', 500);
        }
    }
}