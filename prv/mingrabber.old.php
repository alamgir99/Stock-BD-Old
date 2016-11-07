<?php
//#!/usr/local/bin/php     
$dir = dirname(dirname(__FILE__)); // location of  root dir
include_once $dir.'/config.php'; // config info

if($sb_market_open === false)
exit(0);

include_once $dir.'/bare.php'; // basic logging functions

date_default_timezone_set("Asia/Dhaka"); 

//grabs the latest price table from BRAC, GQ or DSE
// make it switchable
//parses and saves as per ticker csv file
$datasource = 'gq'; // brac, gq or dse

//DOM parser class
include_once(SBD_ROOT.'/prv/simple_html_dom.php');
    
// price table of last snapshot
$snapshot_old = array(); 
//price table of current snapshot
$snapshot_new = array(); 
//different of price table of current and old snapshot
$snapshot_del = array(); 

//a flag indicating whether this snap is the first one
if(file_exists(SBD_ROOT.'/prv/temp/snapshot.txt'))
    $firstsnap = false;
else
    $firstsnap = true;    


//start time
$time_start = microtime_float();

//read if any existing snap
read_last_snap();

//time instant of capture
$time_sig = date("H:i:s"); 

//capture data based on source    
if($datasource == 'brac'){
    include(SBD_ROOT.'/prv/bracepl.php');
}
else if($datasource == 'gq'){
	//get the price table from GQ
	include(SBD_ROOT.'/prv/gqsec.php');
}
else if ($datasource=='dse'){
	//use this if GQ has got a problem
	//get the price table from DSE
	include(SBD_ROOT.'/prv/dse.php');
}

//get current snapshot
get_price_table();

 //$snapshot_new now has the current snapshot

//find the delta
 find_delta();
 
 //save the delta and snapshot
 save_data();
 
 
 // create an interim CSV -- 
 // TODO: open prices are lost now
 // create_save_int_csv();
 
$time_end = microtime_float();
$time_el = $time_end - $time_start;
$time_el = sprintf("%01.2f", $time_el);

log_msg(__FILE__, "Min data grabbed, took $time_el seconds");
exit(0);

// supporting functions below

//reads the last snapshot file from temp folder
function read_last_snap()
{
    global $snapshot_old;
    global $firstsnap;
    
    if($firstsnap) return;
    
    $filename =  SBD_ROOT.'/prv/temp/snapshot.txt';   
    if(file_exists($filename))
    {
        $fid =  fopen($filename, "r");
        if($fid === false){
                log_msg(__FILE__, "Error reading file ".$filename);
                exit(0);
         }
    
        $buffer = fread($fid, filesize($filename));
        fclose($fid);
        
        $lines = preg_split("/[\n]+/", $buffer);
        
        foreach($lines as $line){
            $vars = preg_split("/[,]+/", $line);
            if($vars[0]=='') break;
                                     // 0->ticker, 1->lt, 2->hi, 3->lo, 4->vol 
            $snapshot_old[$vars[0]]['lt'] = $vars[1];
            $snapshot_old[$vars[0]]['hi'] = $vars[2];
            $snapshot_old[$vars[0]]['lo'] = $vars[3];
            $snapshot_old[$vars[0]]['vl'] = $vars[4];
        }
    }
}





//finds the delta of two shanpshot
function find_delta()
{
     global $snapshot_del, $snapshot_new, $snapshot_old;
    
     if(count($snapshot_old)==0) return;
     if(count($snapshot_new)==0) return;
     
     foreach($snapshot_old as $ticker => $snap)
        {
           if(isset($snapshot_new[$ticker])) // ticker name match
           {
             if($snapshot_new[$ticker]['vl']>= $snapshot_old[$ticker]['vl'])  // if higer vol so a real trade
             {  
                 $snapshot_del[$ticker]['lt'] = $snapshot_new[$ticker]['lt']; // last trade price
                 $snapshot_del[$ticker]['vl'] = $snapshot_new[$ticker]['vl']-$snapshot_old[$ticker]['vl'];
             }
             else
             {
                 $snapshot_del[$ticker]['lt'] = $snapshot_old[$ticker]['lt']; 
                 $snapshot_del[$ticker]['vl'] = 0;
             }
             
             //fowlowing happens if a sharp rise/fall happens withing the measuring interval 
             if($snapshot_new[$ticker]['hi'] > $snapshot_old[$ticker]['hi'])
                    $snapshot_new[$ticker]['lt'] = $snapshot_new[$ticker]['hi']; // HH becomes LTP
             
             if($snapshot_new[$ticker]['lo'] < $snapshot_old[$ticker]['lo'])
                    $snapshot_new[$ticker]['lt'] = $snapshot_new[$ticker]['lo']; // LL becomes LTP
                    
           }            
        } // for each
}
 
 function save_data()
 {
     global $snapshot_new;
     global $snapshot_del;
     global $snapshot_old;
    
     global $yclose;
     global $firstsnap;
     global $time_sig;
     
     
     if(count($snapshot_new)==0) return;

     //save the current snapshot
    $filename =  SBD_ROOT.'/prv/temp/snapshot.txt';   
    $fid =  fopen($filename, "w");
    if($fid === false){
        log_err(__FILE, "Error creating file ".$filename);
        exit(0);
    }
       
    foreach($snapshot_new as $key => $tuple){        	 
    	fprintf($fid, $key.','.implode(",", $tuple));
        fprintf($fid, "\n");
    }  
    fclose($fid);
    
     
        
    // get the trading date year
    $date_yr= date("Y");
    
    //whole date
    $date_sig = date("Y-m-d");
    $wd = SBD_ROOT.'/data/mindata/'.$date_yr.'/'.$date_sig;
    
    
    //no delta yet, then nothing to proceed
    if(count($snapshot_del)==0) return;
    
    //now save one file for each ticker
    foreach($snapshot_del as $ticker => $val){
        $filename = $wd .'/'.$ticker.'.txt';
        $fid = fopen($filename, 'a');
        if($fid !== false){ // created ok
            fprintf($fid, $time_sig.','.implode(',', $val));
            fprintf($fid, "\n");
            fclose($fid);            
        }
    }
 }


//create an interim CSV and sectoral turnover things
 function create_save_int_csv()
 {
     global $snapshot_new;

     return;
     
    // get the trading date year
    $date_yr= date("Y");
    //whole date
    $date_sig = date("Y-m-d");
    
    $fname = SBD_ROOT.'/data/csvdata/'.$date_yr.'/'.'dse-'.$date_sig.'.csv';
    
    $fid = fopen($fname, "w");
    if($fid === false){
        log_err(__FILE__, ' Cant create file :'.$fname);
        return;
    }
    
    foreach($snapshot_new as $tick => $snap){
        fwrite($fid, $tick.', '.$date_sig.', '.$snapshot_new[$tick]['op'].', '.$snapshot_new[$tick]['hi'].', '.$snapshot_new[$tick]['lo'].', '.$snapshot_new[$tick]['lt'].', '.$snapshot_new[$tick]['vl']."\n");
    }
    fclose($fid);     
 }
