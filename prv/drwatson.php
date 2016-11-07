<?php
// diagnostic mail from stock-bd

$dir = $dir = dirname(dirname(__FILE__));  
$fname = $dir.'/log/errlog.txt';

if(file_exists($fname)===false)
	exit;


$to = 'alamgir99@gmail.com';
$from = 'stockbd@gmail.com';


$message = file_get_contents($fname); 
$subject = 'Error in stock-bd.com';


if(pun_mail($to, $subject, $message, $from))
echo 'success';
else
echo 'failed'.

exit;


  function pun_mail($to, $subject, $message, $from = '')
{

    // Default sender/return address
    // Do a little spring cleaning
    $to = trim(preg_replace('#[\n\r]+#s', '', $to));
    $subject = trim(preg_replace('#[\n\r]+#s', '', $subject));
    $from = trim(preg_replace('#[\n\r:]+#s', '', $from));

    $headers = 'From: '.$from."\r\n".'Date: '.date('r')."\r\n".'MIME-Version: 1.0'."\r\n".'Content-transfer-encoding: 8bit'."\r\n".'Content-type: text/plain; charset=utf-8'."\r\n".'X-Mailer: PunBB Mailer';

    // Make sure all linebreaks are CRLF in message (and strip out any NULL bytes)
    $message = str_replace(array("\n", "\0"), array("\r\n", ''), pun_linebreaks($message));

    // Change the linebreaks used in the headers according to OS
    if (strtoupper(substr(PHP_OS, 0, 3)) == 'MAC')
         $headers = str_replace("\r\n", "\r", $headers);
     else if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN')
         $headers = str_replace("\r\n", "\n", $headers);

      return mail($to, $subject, $message, $headers);
}

function pun_linebreaks($str)
{
    return str_replace("\r", "\n", str_replace("\r\n", "\n", $str));
}

?>
