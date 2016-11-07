<?php
/*
Minute data capture script.
Last update on: Dec 23, 2011

Changelog:
    Dec 26, 2011: Added snapshot counter for numbering the snapshots.
                  Modified the last_snap_read function to read any old snapshot
                  Added code to produce candle on every 5 snapshots.
                  Snapshots are saved for debuging purpose.


*/

//define('__DEBUG__ON', '1'); // 1 when debugging

//grabs the latest price table from BRAC, GQ or DSE
//*********************************************//
$datasource = 'dse'; // brac, gq or dse
$dseurl     = 'http://dse.com.bd';
//********************************************//

$dir = dirname(dirname(__FILE__)); // location of  root dir
include_once $dir.'/config.php'; // config info

if($sb_market_open === false)
exit(0);

include_once $dir.'/bare.php'; // basic logging functions

date_default_timezone_set("Asia/Dhaka"); 

//DOM parser class
include_once(SBD_ROOT.'/prv/simple_html_dom.php');
    
// price table of last snapshot
$snapshot_old = array(); 
//price table of current snapshot
$snapshot_new = array(); 
//different of price table of current and old snapshot
$snapshot_del = array(); 

//what snapshot # are we in
$snapshot_count = file_get_contents(SBD_ROOT.'/prv/temp/snapcounter.txt');
if($snapshot_count===FALSE) // file does not exist
    $snapshot_count = 1; // snap 1
    
//start time
$time_start = microtime_float();

//read if any existing snap
if($snapshot_count > 1) // not the first one
    $snapshot_old = read_snapshot($snapshot_count-1);

//whole date
$date_sig = date("Y-m-d");
//short date
$date_sig_s = date("ymd");
        
//time instant of capture
$time_sig = date("H:i:s"); 
//short time sig
$time_sig_s = date("Hi"); 

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

  
 //do the 5-min candle processing here
 if(($snapshot_count%5) == 0) // becomes true on every 5th snapshot
 {                              // 5, 10, 15, 20, 25, 30, 35 etc
     $snap5 = $snapshot_new; //5th
     $snap4 = $snapshot_old; //4th
     
     //free up memory
     unset($snapshot_new); unset($snapshot_old); unset($snapshot_del);
     
     //read the older snapshots
     $snap3 = read_snapshot($snapshot_count-2); // 5-2 = 3rd
     $snap2 = read_snapshot($snapshot_count-3); // 5-3 = 2nd
     $snap1 = read_snapshot($snapshot_count-4); // 5-4 = 1st
     
     //form the candles
     foreach($snap5 as $tick => $vals) {
         //an array of ltps of last 5 snaps
         $ltps = array($snap1[$tick]['lt'], $snap2[$tick]['lt'], $snap3[$tick]['lt'], $snap4[$tick]['lt'],$snap5[$tick]['lt']);
         
         $op = $snap1[$tick]['lt']; // ltp of first snap is the open price of 5-min candle
         $cl = $snap5[$tick]['lt']; // ltp of fifth snap is the close price of 5-min candle
         $lo = min($ltps);                     // min of ltp of snap 1 thru 5
         $hi = max($ltps);                     // max of ltp of snap 1 thru 5
         $vl = (int)($snap5[$tick]['vl']-$snap1[$tick]['vl']); // vol are cumulative, so get the diff
         if( $vl <= 0 )
            $vl = $snap5[$tick]['vl'];
         
         // purify the data   
         if($op != '' && $cl != '' && $hi != '' && $lo != '' && $vl != 0)   {
             $snap[$tick] =  array('op' => $op, 'hi' => $hi, 'lo' => $lo, 'cl' => $cl, 'vl' => $vl);
         }                    
     }
  
     //save the candles
     $fname =  SBD_ROOT.'/prv/temp/candle-'.sprintf("%03d",$snapshot_count).'.csv';   
     $fid = fopen($fname, "w");
     if($fid){
         foreach($snap as $tick => $vals){
             fwrite($fid, $tick.','.$date_sig_s.','.$time_sig_s.','.$vals['op'].','.$vals['hi'].','.$vals['lo'].','.$vals['cl'].','.$vals['vl']);
             fwrite($fid, "\n");
         }
         fclose($fid);
     }
 }
 
 // update the snapshot counter
 $snapshot_count++;
 file_put_contents(SBD_ROOT.'/prv/temp/snapcounter.txt', $snapshot_count);
 
$time_end = microtime_float();
$time_el = $time_end - $time_start;
$time_el = sprintf("%01.2f", $time_el);

log_msg(__FILE__, "Min data grabbed, took $time_el seconds");

//echo "Adv :".$snapshot_new['00ADV']['lt'].", Vol:".$snapshot_new['00ADV']['vl']."\n";
//echo "Dec :".$snapshot_new['00DEC']['lt'].", Vol:".$snapshot_new['00DEC']['vl']."\n";
//echo "Unc :".$snapshot_new['00UNC']['lt'].", Vol:".$snapshot_new['00Unc']['vl']."\n";

exit(0);

// supporting functions below

//reads a snapshot file from temp folder
function read_snapshot($snap_no)
{
    $snapshot = array();
    
    $filename =  SBD_ROOT.'/prv/temp/snapshot-'.sprintf("%03d",$snap_no).'.csv';   
    if(file_exists($filename))
    {
        $fid =  fopen($filename, "r");
        if($fid === false){
                log_msg(__FILE__, "Error reading file ".$filename);
                return null;
         }
    
        $buffer = fread($fid, filesize($filename));
        fclose($fid);
        
        $lines = preg_split("/[\n]+/", $buffer);
        
        foreach($lines as $line){
            $vars = preg_split("/[,]+/", $line);
            if($vars[0]=='') break;
                                     // 0->ticker, 1->lt, 2->hi, 3->lo, 4->vol 
            $snapshot[$vars[0]]['lt'] = $vars[1];
            $snapshot[$vars[0]]['hi'] = $vars[2];
            $snapshot[$vars[0]]['lo'] = $vars[3];
            $snapshot[$vars[0]]['vl'] = $vars[4];
        }
       return $snapshot; 
    }
    else
        return null;
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
     global $snapshot_count;
     global $time_sig;
     global $date_sig;
     
     
     if(count($snapshot_new)==0) return;

     //save the current snapshot
    $filename =  SBD_ROOT.'/prv/temp/snapshot-'.sprintf("%03d",$snapshot_count).'.csv';   
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

     return; // dont use it yet
     
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
