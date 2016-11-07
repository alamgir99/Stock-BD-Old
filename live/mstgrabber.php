<?php
//#!/usr/local/bin/php

$dir = dirname(dirname(__FILE__)); // location of  root dir
include_once $dir.'/config.php'; // config info

if($sb_market_open === false)
exit(0);

include_once $dir.'/bare.php'; // basic logging functions
// db functions
include_once SBD_ROOT."/prv/basicdb.php";   

$link1 =  "http://www.dsebd.org"; 
$link2 =  "http://webnew.dsebd.org";
$link3 =  "http://admin.dsebd.org";


$datatable = array(); 
$gainerlooser = array();
$tdate = date("Y-m-d");
$sectors = array();
$sids = array();
$facevalues = array();
$sec_facevalues = array();

//start time
$time_start = microtime_float();
 //read sector definitions
//read_sectors();
// read the face values
read_facevalues();
//determine face values of the sectors
//find_sector_facevalues();

//grab the mst file from dse server
get_mst();

//process the file and save
process_mst();

//update sector data
//update_sector_data(); // do it in a separete PHP, this place is not accurate as it's based on LTP

$time_end = microtime_float();
$time_el = $time_end - $time_start;
$time_el = sprintf("%01.2f", $time_el);
log_msg(__FILE__,"MST grabbed, converted to CSV. Took $time_el seconds.");
 
exit(1);

// supporting functions are below   

 //gets mst file
 function get_mst()
 {
     global $link1, $link2, $link3;
     
 // get the mst file - begins
	$cmd = 'curl  '.$link1.'/mst.txt -o  '.SBD_ROOT.'/prv/temp/mst.txt';
	$err_code = exec ($cmd);
    
    if(test_mst()=== false) // try second link
    {
        $cmd = 'curl  '.$link2.'/admin-real/mst.txt -o  '.SBD_ROOT.'/prv/temp/mst.txt';
        $err_code = exec ($cmd);
        
        if(test_mst()===false){ // try third link
            $cmd = 'curl  '.$link3.'/admin-real/mst.txt -o  '.SBD_ROOT.'/prv/temp/mst.txt';
            $err_code = exec ($cmd);
            
            if(test_mst()===false){ // last attempt fails too
                log_err(__FILE__,"Date mismatch in MST file. Guess the link changed."); 
                exit(0);
            }
        }
    }    
 // get the mst file - ends
 
 // get the dgen html file - begins
 unlink(SBD_ROOT.'/prv/temp/dsegen.htm');
 $cmd = 'curl http://dsebd.org/index-graph/companygraph.php?graph_id=gen -o   '.SBD_ROOT.'/prv/temp/dsegen.htm';
 $err_code = exec ($cmd);
 
 if(filesize(SBD_ROOT.'/prv/temp/dsegen.htm') < 100) // file move, try on web.dsebd.org
	{
	    $cmd = 'curl http://backup.dsebd.org/index-graph/companygraph.php?graph_id=gen -o   '.SBD_ROOT.'/prv/temp/dsegen.htm';
	    $err_code = exec ($cmd);
	    if($err_code != 0) {
	    //error, write a log
	    log_msg(__FILE__, 'Failed to get dsegen htm file from dse server.'); 
	    exit(0);
	    }
     }
   // get the dsegen html file - ends
 
 }
 
 // this little function checks the date signature of the 
 // trading, if there is a mismatch returns false
 function test_mst()
 {
     global $tdate;
     
     //return true;
     
     $fname = SBD_ROOT.'/prv/temp/mst.txt';
     $fid = fopen($fname, "r");
     if($fid===false)
        return false;
        
     $buffer = fread($fid, 200);
     $bpos = strpos($buffer, "TODAY'S SHARE MARKET :");
     if($bpos !== false) // found
     {
         $date_sig = trim(substr($buffer, $bpos+22, 12)); // date signature
     }
     
     if($date_sig == $tdate)
        return true;
     else 
        return false;   
 }

 
 // this function processes the mst file
function process_mst()
{
    global $datatable;    // need to access to save as csv
    global $sectors;
    global $facevalues;
    global $sec_facevalues;
    global $tdate;

    
    $filename = SBD_ROOT.'/prv/temp/mst.txt';
    $fid = fopen($filename, 'r');
    if($fid == null) {
	    log_msg(__FILE__, 'Failed to open file: '.$filename); 
	    exit(0);
     }	

     //read the file and close it
     $buffer = fread($fid,filesize($filename));
     fclose($fid);
     

     $bpos = strpos($buffer, "TODAY'S SHARE MARKET :");
     if($bpos !== false) // found
     {
         $date_sig = trim(substr($buffer, $bpos+22, 12)); // date signature
         $tdate  = $date_sig;
         $date_yr  = substr($date_sig,0, 4);  // just the year part                  
     }
     else{
       log_msg(__FILE__, 'Error in parsing MST for date.'); 
       exit(0);  
     }
     
    
     //find the dsegen open and close, and vol
     $bpos = strpos($buffer, "DSE GENERAL INDEX (DGEN)");
     $epos = strpos($buffer, "All Category");
     if($bpos !== false && $epos > $bpos) {
         $bpos += 25; // get past the signatur string
         $opcl = trim(substr($buffer, $bpos, $epos-$bpos));
         $opclos = preg_split("/[\s]+/",$opcl);
         $dgen_cl = trim($opclos[0]);            // dse gen close
         $dgen_op = $dgen_cl - trim($opclos[1]);  // dse gen open
     }
     else {
        log_msg(__FILE__, 'Error in parsing MST for dse open/close.'); 
        exit(0);  
     }

     // trade vol and turnover
     $dgen_vol = 0;
     $dse_to = 0;
     //find the trade volume
     $bpos = strpos($buffer, "VOLUME(Nos.)");
     if($bpos !== false)
     {   $bpos += 12;
         $i = $bpos;
         while($buffer[$bpos]==' ' || $buffer[$bpos]==':')
            $bpos++;
         $i = $bpos; $bpos++;
            
         while(is_numeric($buffer[$bpos]))
            $bpos++;
         
        //get the vol value
        $dgen_vol = trim(substr($buffer,$i, $bpos-$i));                  
     }
     
     //find the trade turn over
     $bpos = strpos($buffer, "VALUE(Tk)");
     if($bpos !== false)
     {   $bpos += 9;
         $i = $bpos;
         while($buffer[$bpos]==' ' || $buffer[$bpos]==':')
            $bpos++;
         $i = $bpos; $bpos++;
            
         while(is_numeric($buffer[$bpos]))
            $bpos++;
         
        //get the TO value, in millions
        $dse_to = trim(substr($buffer,$i, $bpos-$i))/1000000;                  
     }
     
   //rename the mst file and save it  
   $mstfilename = SBD_ROOT.'/data/mstdata/'.$date_yr.'/mst-'.$date_sig.'.txt';
   $err_code = rename($filename, $mstfilename);
   //if($err_code === false){
   //     log_msg(__FILE__, 'Error renaming the file :'.$mstfilename); 
   //     exit(0);       
   //}
    
   //now process block by block
   while(true){
       $bpos = strpos($buffer, "Instr Code", $epos); // begining of table
       if($bpos !== false)
       {
           $bpos2 = strpos($buffer, "\r\n\r\n", $bpos)+4; // get past the double crln
           
           //lets see if we have the text "Max Price" within this range
           // if so, it must be an odd block
           $testblock = substr($buffer, $bpos, $bpos2-$bpos);
           if(strpos($testblock, "Max Price")=== false){
               $odd = false;
           }
           else {
                $odd = true;
           }
           
           $bpos = $bpos2;
       }
       if($bpos === false) // we are at the end
            break;
       $epos = strpos($buffer, "------", $bpos); // end of a block
       
       if($epos === false)
        break; // we are at the last block
        
        $ablock = substr($buffer, $bpos, $epos-$bpos-1);
        process_block($ablock, $odd);   
   }
   
   //get the dsegen hi/lo
   //now handle the index file to get hi and lo
    $filename = SBD_ROOT.'/prv/temp/dsegen.htm';
    $fid = fopen($filename, 'r');
    if($fid == null) {
        log_err(__FILE__, 'Failed to open file :'.$filename); 
        exit(0);
     }    
     
     //read the file and close it
     $buffer = fread($fid,filesize($filename));
     fclose($fid);
     
     $bpos = strpos($buffer, "</strong>");
     $epos = strpos($buffer, "</font>", $bpos); 
     $dgen_hi = trim(substr($buffer, $bpos+9, $epos-$bpos-9));
     
     $bpos = strpos($buffer, "</strong>", $epos);
     $epos = strpos($buffer, "</font>", $bpos); 
     $dgen_lo = trim(substr($buffer, $bpos+9, $epos-$bpos-9)); 
     
     //add the dsegen trade vol
     $datatable["00DSEGEN"] = array($dgen_op, $dgen_hi, $dgen_lo, $dgen_cl, $dgen_vol);
     
     //add the dsegen trade vol
     $datatable["00DSETO"] = array($dgen_op, $dgen_hi, $dgen_lo, $dgen_cl, $dse_to);
     
	 //calculate the gainer and looser
	 $fname = SBD_ROOT.'/data/yclose.txt';
     $fid = fopen($fname, "r");
     if($fid){
		$buffer = fread($fid,filesize($fname));
		$buffer = preg_split("/[\r\n]+/", $buffer); // split each symbol
		foreach($buffer as $pair){
			$pair = explode(",", $pair); // split the ticker and close price
            $tick = trim($pair[0]);
            if(substr($tick,0,2) == "00") 
                        continue;
            
			$tickers[$tick] = trim($pair[1]);
		}	
	 }
	 else
	 {
       log_msg(__FILE__, 'Error reading file :'.$fname); 
        exit(0);    
     }
	 
	 $adv_issue = 0;
	 $adv_vol   = 0;
	 $dec_issue = 0;
	 $dec_vol   = 0;
	 
	 foreach($tickers as $tick => $ycp)
	 {
		if($datatable[$tick]) // being traded today
		{
			if($datatable[$tick][3] > $ycp){
				$adv_issue++;
				$adv_vol += $datatable[$tick][4];
			}
			else if($datatable[$tick][3] < $ycp){
				$dec_issue++;
				$dec_vol += $datatable[$tick][4];
			}
		}
	 }
	 
	 // add the tickers
	 $datatable["00ADV"] = array($adv_issue, $adv_issue, $adv_issue, $adv_issue, $adv_vol);
	 $datatable["00DEC"] = array($dec_issue, $dec_issue, $dec_issue, $dec_issue, $dec_vol);
	 
     //save only the closing values of all tickers for future index calculation on next day
     $fname = SBD_ROOT.'/data/yclose.txt';
     $fid = fopen($fname, "w");
     if($fid === fasle){
       log_msg(__FILE__, 'Error creating file :'.$fname); 
        exit(0);    
     }
     //write the data
     foreach($datatable as $ticker => $data)
     {
       fwrite($fid, $ticker.', ');
       fwrite($fid, $data[3]."\n");
     }
       fclose($fid);
   
     
     //now compute the sectoral data
     foreach($sectors as $sector => $tickers){
            $op = 0; $hi = 0; $lo = 0; $cl = 0; $vl = 0;
            $cnt = 0;
            foreach($tickers as $tick) {
                if(isset($facevalues[$tick])){
                    $factor = $facevalues[$tick]/$sec_facevalues[$sector];
                          //// tick, open, high, low, close, vol  
                    $op += $datatable[$tick][0]/$factor;
                    $hi += $datatable[$tick][1]/$factor;
                    $lo += $datatable[$tick][2]/$factor;
                    $cl += $datatable[$tick][3]/$factor;
                    $vl += $datatable[$tick][4]*$factor;
                    $cnt++;
                }
            }
            $op = $op/$cnt; $op = sprintf("%1.2f", $op);
            $hi = $hi/$cnt; $hi = sprintf("%1.2f", $hi);
            $lo = $lo/$cnt; $lo = sprintf("%1.2f", $lo);
            $cl = $cl/$cnt; $cl = sprintf("%1.2f", $cl);
            
            $datatable[$sector] = array($op, $hi, $lo, $cl, intval($vl));
     }

     
   //sort the tickers
   ksort($datatable);
   
   //form and save csv file
   $csvfilename = SBD_ROOT.'/data/csvdata/'.$date_yr.'/dse-'.$date_sig.'.csv'; 
   $fid = fopen($csvfilename, "w");
   if($fid===false)
   {
       log_err(__FILE__, 'Error creating csv file :'.$csvfilename); 
        exit(0);  
   }
   
   //write the data
   foreach($datatable as $ticker => $data)
   {
       fwrite($fid, $ticker.', '.$date_sig.', ');
       fwrite($fid, implode(',', $data)."\n");
   }
   fclose($fid);
}

//process a block and put it into the global array
function process_block($block, $odd)
{
   global $datatable; 
   global $gainerlooser;
   
   $lines = preg_split("/[\r\n]+/",$block);
   $nlines = count($lines);
   
   if($odd===false) // a regular block
   {
        foreach($lines as $line)
        {
           $vals = preg_split("/[\s]+/",$line);
           $ticker = trim($vals[0]);
           if($ticker=="") 
                continue;
                           // open, high, low, close, vol  -- skip change and trade
           $onetick = array($vals[1],$vals[2],$vals[3],$vals[4], $vals[7],);
            
           //update the global data table
           if(!isset($datatable[$ticker])) // the ticker is not already in the table
           {
             $datatable[$ticker]= $onetick;
             if($vals[5]>0)
                $gainerlooser[$ticker] = 1;
             else if($valus[5] < 0)
                $gainerlooser[$ticker] = -1;   
             else
                $gainerlooser[$ticker] = 0;    
           }
       }
   }
   else { // an odd block
    foreach($lines as $line){
        $vals = preg_split("/[\s]+/",$line);
        $ticker = trim($vals[0]);
        if($ticker=="") 
            continue;
                        //just take the vol count
        $onetick = $vals[4];
        if(isset($datatable[$ticker])) // the ticker is already in the table
         {
              $datatable[$ticker][4] += $onetick;
         }
    }    
   } // odd block 
   return 0;
}


function read_sectors()
{
    global $sectors, $sids;
    global $db;
    
     $sector_names = array();
     //add sectoral tickers
     $sids = array();  
     $sql = 'SELECT s_id, sector_name FROM sectors';
     $res = $db->query($sql);
     $i = 0;
     if($res){
         while($data = $db->fetch_row($res))
         {
             $sids[$data[1]]= $data[0];
             $sector_names[$i]=$data[1];
             $i++;
         }
     }
     
     
     //for each sector read the associated symbols
     foreach($sids as $sectn => $sid){
        $sql = 'SELECT ticker from sector_def WHERE s_id='.$sid;
        $res =  $db->query($sql);
        if($res){
            while($data = $db->fetch_row($res))
            {
                 $sectors[$sectn] []=$data[0];
             }
          // free the result  
          $db->free_result($res);
        }
     }     
}

// a list of facevalues
function read_facevalues()
{
    global $facevalues;
    global $db;
    
    $sql = 'SELECT ticker, face_value FROM comp_info';
    $res = $db->query($sql);
    if($res){
        while($row=$db->fetch_row($res)){
            $facevalues[$row[0]]=$row[1];
        }
     
        $db->free_result($res);
    }
}

//this function will go thru the sector definition and
//choose the best FV for the sector based on majority counts
function find_sector_facevalues()
{
    global $sectors;
    global $facevalues;
    global $sec_facevalues;
    
    foreach($sectors as $sector => $tickers){
        // build the fv list of the tickers
        $tick_fv = array();
        foreach($tickers as $tick)
            if(isset($facevalues[$tick]))
                $tick_fv []= $facevalues[$tick];
            
        
        $mode = mmmr($tick_fv, 'mode');
        $sec_facevalues[$sector] = $mode;
    }
}

// update the sectoral turnover etc
function update_sector_data()
{
   global $gainerlooser;
   global $datatable;
   global $sectors, $sids;
   global $db;
   global $tdate;
   
   
   //find total turnover
   $tot_to = 0;
   $sect_to = array();
   $sect_nto = array();
   $sect_gn = array();
   $sect_ls = array();
   $sect_un = array();
   
   foreach($sectors as $sector => $tickers){
       foreach($tickers as $tick){
           $sect_to[$sector] += ($datatable[$tick][3]*$datatable[$tick][4])/1e6;
           if($gainerlooser[$tick]==1)
             $sect_gn[$sector]++;
           else if  ($gainerlooser[$tick]==-1)
             $sect_ls[$sector]++;
           else
            $sect_un[$sector]++;  
       }
       
       $tot_to += $sect_to[$sector];    
   }
   
   //normalised to
   foreach($sectors as $sector => $dummy)
   {
       $sect_nto[$sector] = $sect_to[$sector]*100/$tot_to;
   }
    
   //now update the database
    foreach($sectors as $sector => $ticks)
    {
        $sid = $sids[$sector];
        if(isset($sect_gn[$sector])===false)
            $sect_gn[$sector] = 0;
        
        if(isset($sect_ls[$sector])===false)
            $sect_ls[$sector] = 0;
                
        if(isset($sect_un[$sector])===false)
            $sect_un[$sector] = 0;
                    
        $sql = 'DELETE FROM sector_data WHERE tdate="'.$tdate.'"';
        $db->query($sql);
        $sql = 'INSERT INTO sector_data(tdate, s_id, turnover, nturnover, gainer, looser, unchanged) VALUES(';
        $sql = $sql .'\''.$tdate.'\','.$sid.','.$sect_to[$sector].','.$sect_nto[$sector].','.$sect_gn[$sector].','.$sect_ls[$sector].','.$sect_un[$sector].')';
        $db->query($sql);
    }
}

function mmmr($array, $output = 'mean'){ 
    if(!is_array($array)){ 
        return FALSE; 
    }else{ 
        switch($output){ 
            case 'mean': 
                $count = count($array); 
                $sum = array_sum($array); 
                $total = $sum / $count; 
            break; 
            case 'median': 
                rsort($array); 
                $middle = round(count($array) / 2); 
                $total = $array[$middle-1]; 
            break; 
            case 'mode': 
                $v = array_count_values($array); 
                arsort($v); 
                foreach($v as $k => $v){$total = $k; break;} 
            break; 
            case 'range': 
                sort($array); 
                $sml = $array[0]; 
                rsort($array); 
                $lrg = $array[0]; 
                $total = $lrg - $sml; 
            break; 
        } 
        return $total; 
    } 
}

?>
