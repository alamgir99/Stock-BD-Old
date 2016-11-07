<?php
//minute data server
$dir = dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";

if(isset($_GET['ticker']))
	$ticker = urldecode($_GET['ticker']);
else
	exit;

//date_default_timezone_set("Asia/Dhaka"); 

 //$_GET['lastx']=1289579938;
 
 //if ticker does not exist or tdate does not exists, then error
 
 if(isset($_GET['tdate']))
	$tdate = $_GET['tdate'];
else
	$tdate = date("Y-m-d");
	
	
if(isset($_GET['lastx']))
	$lastx = $_GET['lastx'];
else
	$lastx = 0;

//which file contains the data
$td_yr = substr($tdate, 0, 4);
$fname = SBD_ROOT.'/data/mindata/'. $td_yr.'/'.$tdate.'/'. $ticker.'.txt';
$fid = @fopen($fname, "r");
if($fid===FALSE )
{
echo "Warning: no data yet."; //.$fname;
exit;
}

$outv = array();
$outp = array();
$outx = array();

while(!feof ($fid)){  
    $content = fgetcsv($fid);
    if($content[2]==0) //skip zero vol
	continue;
    //time format from string to number
      $content[0] = strtotime($content[0]);
	if($content[0] > $lastx) // we have newer data
	{
		$outx [] = $content[0]; // time stamp
		$outp [] = $content[1]; // LTP
		$outv [] = $content[2]; // vol
	 }
}

fclose($fid);


$ret1 = "";
$ret2 = "";
if(count($outx)==0)  // data stopped for some reason or trade hour is over
{
  $tradeover = false;
  if($tradeover){
	echo "Finished: trade hour is over.";
	exit;
 }	
 else{
 	echo "Warning: data  delay, will resume automatically.";
	exit;
 }

}

if(count($outx) == 1) // only current point
{
 $ret1 = "[".$outx[0].",".$outv[0]."]"; // volume
 $ret2 =  "[".$outx[0].",".$outp[0]."]"; // price
}
//else the whole array
else{
	for($i=0; $i<count($outx)-1; $i++)
	{
		$ret1 = $ret1 . "[".$outx[$i].",".$outv[$i]."],"; // volume
		$ret2 = $ret2 . "[".$outx[$i].",".$outp[$i]."],"; // price
	}

	//the last point
	$ret1 = $ret1 . "[".$outx[$i].",".$outv[$i]."]"; // volume
	$ret2 = $ret2 . "[".$outx[$i].",".$outp[$i]."]"; // price
}

//wrap around []
 $ret1 = '[' .$ret1 . ']';
 $ret2 = '[' .$ret2 . ']';
     

$bull = 0;
$bear = 0;

$ret3 = array($bull, $bear);

//echo 'Warning: no data available yet. Don\'t refresh, I will do it automatically.';
//echo 'Error: no data available. Choose a different date and/or instrument and click "Get Chart".';
$enc_data = $ret1.';'.$ret2. ';'.json_encode($ret3);
echo $enc_data;
?>

