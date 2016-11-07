<?php
//live data server
$dir = dirname(dirname(__FILE__)); // location of  root dir
include_once $dir.'/config.php'; // config info

if($sb_market_open === false){
    echo "OVER";
    exit;
}

// see if we are on weekend
$day = date("D");
if($day == "Fri" || $day == "Sat")
{
    echo "OVER";
    exit;
}

// check if we are in live session
$timenow = date("G")+date("i")/60; // time as decimal fraction
if($timenow > $sb_tradingend+0.166 ) // trading hour is over, 0.166 means extra 10 mins
{
    echo "OVER";
    exit;
}
// no earlier than 30min
elseif($timenow > $sb_tradingstart-0.5 && $timenow < $sb_tradingstart)    
{
    echo "OK";
    exit;
}
elseif($timenow <= $sb_tradingstart-0.5) // earlier than 30 min
{
    echo "OVER";
    exit;
}
    
//what snapshot # are we in
$snapshot_count = file_get_contents(SBD_ROOT.'/prv/temp/snapcounter.txt');
if($snapshot_count===FALSE) // file does not exist
    $snapshot_count = 1; // snap 1

//spit out the latest candle file we have
$candle_no = $snapshot_count - $snapshot_count%5;
$fname =  SBD_ROOT.'/prv/temp/candle-'.sprintf("%03d",$candle_no).'.csv';   

//echo $fname;

if(file_exists($fname) === false)
{
	echo "WAIT";
    exit;
}
elseif(filesize($fname)==0)    {
    echo "OVER";
    exit;
}
            
//read and spit out the file
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false); // required for certain browsers 
header("Content-Type: application/csv");

header('Content-disposition: attachment; filename='.basename($fname));
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".filesize($fname));
readfile($fname);