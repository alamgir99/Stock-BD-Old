<?php

//path to these file
$dir = dirname(__FILE__);

include $dir.'/config.php';
//this function will be heavily used to log all kinds of message
function log_msg($source, $msg){
  $tm = date('Y-m-d, H:i:s');
  file_put_contents(SBD_ROOT.'/log/msglog.txt', "\n[".$tm.'] '.$source .':'. $msg, FILE_APPEND);
}

function log_err($source, $msg){
  $tm = date('Y-m-d, H:i:s');
  file_put_contents(SBD_ROOT.'/log/errlog.txt', "\n[".$tm.'] '. $source .':'.$msg, FILE_APPEND);
}

 
 //function to measure time taken
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
