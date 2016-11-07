<?php
$dir = dirname(dirname(__FILE__)); // location of  root dir
include_once $dir.'/config.php'; // config info

if($sb_market_open === false)
exit(0);

include_once $dir.'/pub/include/common.php'; // basic logging functions


$indexed_stock = array();


//get the list of stocks
$sql = 'SELECT ticker, tot_sec FROM comp_info WHERE category IN("A", "B", "G","N") AND type="EQ"';
//$sql = 'SELECT ticker, tot_sec FROM comp_info WHERE category IN("A", "B", "G","N")';
$res=$db->query($sql);
if($res){
     while(list($tick, $cnt) = $db->fetch_row($res)){
        $indexed_stock[$tick] = (float)$cnt;
    }
}

//save as a php file so that the index calculator can just include it

$fname = $dir.'/prv/temp/indexedstocks.txt';
$fid = fopen($fname, "w");
if($fid===false){
    log_msg(__FILE__, "Can't create file ".$fname);
    exit(0);
}
foreach($indexed_stock as $tick => $cont)
{
    fwrite($fid, $tick.','.$cont."\n");
}
fclose($fid);
