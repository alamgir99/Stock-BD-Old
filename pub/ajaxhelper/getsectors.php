<?php

/* 
Shows tickers for a given sector name

*/

$dir = dirname(dirname(dirname(__FILE__))); // location of  root dir
include $dir."/config.php";

$con = @mysql_connect($db_host, $db_username, $db_password ) or die(mysql_error());
@mysql_select_db($db_name) or die(mysql_error());

$sector = mysql_real_escape_string($sector);

$sql = 'SELECT sector_name FROM sectors';

$result=mysql_query($sql);
$dse_sectors = array();
if($result)
{
 for ($i=0; $i<mysql_num_rows($result); ++$i)
			array_push($dse_sectors, mysql_result($result,$i));
}

//close the connection
@mysql_close($con);

echo implode(',',$dse_sectors);