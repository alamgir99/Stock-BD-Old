<?php

/* 
Shows tickers for a given sector name

*/

if(isset($_GET['sector']))
	$sector = trim($_GET['sector']);
else{
    exit;
  //  $sector = "02_IT";
}

$dir = dirname(dirname(dirname(__FILE__))); // location of  root dir
include $dir."/config.php";

$con = @mysql_connect($db_host, $db_username, $db_password ) or die(mysql_error());
@mysql_select_db($db_name) or die(mysql_error());

$sector = mysql_real_escape_string($sector);

$sql = 'SELECT s_id FROM sectors WHERE sector_name=\''.$sector.'\';';
$sector_id = 0;

$result=mysql_query($sql);
if($result)
{
  $sector_id = mysql_result($result, 0);
}

//if($sector_id == 0)
//exit;

$sql = 'SELECT ticker FROM sector_def WHERE s_id=' .$sector_id .';';
$tickers = array();
$result = mysql_query($sql);
if($result)
{
 for ($i=0; $i<mysql_num_rows($result); ++$i)
			array_push($tickers, mysql_result($result,$i));
}

//close the connection
@mysql_close($con);

echo '[\''.implode('\',\'',$tickers).'\']';

/*
$no_ticker = count($tickers);
$no_col = (int)($no_ticker/15)+1; // 15 ticker name pr column 

echo '<table>';
$k = 0;
for($row=0; $row<15; $row++){
	echo '<tr>';
	for($col = 0; $col < $no_col; $col++){
		if($k < $no_ticker)
			echo '<td style="padding:3px;">' .$tickers[$k++] .'</td>';
		else
			echo '<td></td>';
        }
	echo '</tr>';
}
echo '</table>';
*/
