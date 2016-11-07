<?php

/* Imports data from a CSV file 
  -- calculates all the things we need
  -- puts into sector data etc

 ***** Dont use as Cron job. This is manual tool. *****  
  
  */
//---------------------------------------------  
$base_dir = "/www/stockbd";

//include db settings
require_once $base_dir."/config.php";


// set the default timezone to use. Available since PHP 5.1
date_default_timezone_set('Asia/Dacca');

// get the trading date
$tdate= date("Y-m-d");
$tdates = array($tdate);

// an array of trading days
  $tdates = array(
			'2012-03-01',
			'2012-03-04', 
			'2012-03-05',
			'2012-03-06',
			'2012-03-07',
			'2012-03-08',
			'2012-03-11',
			'2012-03-12',
			'2012-03-13',
			'2012-03-14',
			'2012-03-15',
			'2012-03-18',
			'2012-03-19',
			'2012-03-20',
			'2012-03-22',
			'2012-03-25' 
			);

//----------------------------------------------

$con = @mysql_connect($db_host, $db_username, $db_password) or die(mysql_error());
@mysql_select_db($db_name) or die(mysql_error());

//process each day
foreach($tdates as $tdate) {
	$tyear = substr($tdate, 0, 4);
	$csvfile = $base_dir.'/data/csvdata/'.$tyear.'/dse-'.$tdate .'.csv'; 
// this the format of csv file, I might put the files in a different directory
  
	//check if the file exists
	if(!file_exists($csvfile)) {
		echo "\nNot found: ".$csvfile;;
		exit;
	}

	//open the file
	$fileptr = fopen($csvfile,"r");

	if(!$fileptr) {
		echo "\nError opening data file.\n";
		exit;
	}

	$fsize = filesize($csvfile);
	if(!$fsize) {
		echo "\nFile is empty.\n";
		exit;
	}

      echo "\n Processing ". $csvfile;
	//read the file content
	$csvcontent = fread($fileptr,$fsize);

	//now close the file
	fclose($fileptr);

	//init some array
	//$closeprice = array();
	//$volume     = array();
	$turnover        = array();
	$nturnover      = array();
	$totturnover = 0;

	foreach(explode("\n", $csvcontent) as $line) {
		$line = trim($line," \t");
		$line = str_replace("\r","",$line);
	
		if($line == "") 
		continue;
	
		$linearray = explode(",",$line);
	
		$ticker = trim($linearray[0]);
		$closeprice = $linearray[5];
		$volume     = $linearray[6];
	
        //skip index, and sector tickers
        if(is_numeric(substr($ticker, 0, 2)))
			continue;
	
		//calculate the TO
		$turnover[$ticker] = ($closeprice*$volume)/1e6; //in millions
		$totturnover = $totturnover + $turnover[$ticker];
	} // for each


	//calculate the noralised TO of each ticker
	foreach($turnover as $ticker => $to) {
		$nturnover[$ticker] = $to *100/$totturnover;
	//	echo "\n" .$ticker ." - ". $cap . "-".$nmcap[$ticker];
	}



	// sectorwise TO
	//find all sectors
	$sql = 'SELECT DISTINCT s_id FROM sectors';
	if($result = mysql_query($sql))
	{
		$sectors = array();
		for ($i=0; $i<mysql_num_rows($result); ++$i)
			array_push($sectors, mysql_result($result,$i));
	}
    	
	//echo implode("\n", $sectors);
	//exit;
	
	foreach($sectors as $sector) {
		$sid = (int)$sector; // sector id
        if($sid == 0)  // not assigned
            continue;
	
		// find the list of instrument in that sector
		$sql = 'SELECT ticker from sector_def WHERE s_id =' .$sid .';';
		$result = mysql_query($sql);
		$instruments  = array();
		if($result){
			for ($i=0; $i<mysql_num_rows($result); ++$i)
				array_push($instruments, mysql_result($result,$i));
		}
		

		$sector_to = 0; //sector TO
		$sector_nto = 0; //normalised sector TO
		
		foreach($instruments as $ticker){ // for each instument get the mcap
			$sector_to = $sector_to + $turnover[$ticker];
			$sector_nto = $sector_nto + $nturnover[$ticker];
		}
		
		//a small check if the sector data has already been inserted
		$sql    = 'SELECT s_id from sector_data WHERE s_id=\'' .$sid .'\'  AND tdate=\'' .$tdate. '\'';
		$result = mysql_query($sql);
		if($result) {
			if (mysql_num_rows ($result ) > 0) {// existing data, please update
				$sql = 'UPDATE sector_data SET mcap='. $sectorcap .', nmcap='.$sectorncap .' WHERE s_id=\'' .$sid .'\'  AND tdate=\'' .$tdate. '\'';
				@mysql_query($sql);
				continue;
			}
		}
		
		$sql = 'INSERT INTO sector_data(tdate, s_id, turnover, nturnover) VALUES(\'' .$tdate .'\',' .$sid .',' .$sector_to . ',' .$sector_nto .');';
		//echo $sql . "\n";
		
		//exit;
		//mysql_evaluate($sql);
		@mysql_query($sql);
	}//for each sector
} // for each trading day

@mysql_close($con);
