<?php
//#!/usr/local/bin/php     
$dir = dirname(dirname(__FILE__)); // location of  root dir
include $dir.'/config.php'; // config info
include $dir.'/bare.php'; // basic logging functions
//DOM parser class
include SBD_ROOT.'/prv/simple_html_dom.php';
    
// price table of last snapshot
$snapshot_ds = array(); 
//price table of current snapshot
$snapshot_gq = array(); 

$snapshot_del = array(); 

get_price_table_gq();
get_price_table_dse();
find_delta();
save_data();

//this function gets the price table from GQ and fills the global var $snapshot_new
//change this if the provider is changed
function get_price_table_gq()
{
    global $snapshot_gq;
    
    $fname = SBD_ROOT.'/others/gqs.htm';
    $html = file_get_html($fname);

    $ret = $html->find('table');
    $datatable = $ret[7];
    $datatext = $datatable->plaintext;
    $startat = strpos($datatext,'VOL DIFF')+8; 
    $datatext =  substr($datatext,$startat);

    //echo $datatext;
    $dataarray = preg_split("/[\t]+/", $datatext);
    unset($datatext);
    
    foreach($dataarray as $data){
    	    $data = str_replace(',', '', $data); // revome the thousand separator comma
    		$vals = preg_split("/[\r\n\s]+/", $data);
    		if($vals[0]=="") continue;
    		
            $ticker = trim($vals[0]); //ticker     
            $snapshot_gq[$ticker] = array(  
                                     "op" => trim($vals[4]),
                                     "hi" => trim($vals[5]), //high
                                     "lo" => trim($vals[6]), //low
                                     "lt" => trim($vals[7]), //close/last trade
                                     "vl" => trim($vals[8])); // vol            
        }
}      


function get_price_table_dse()
{
    global $snapshot_ds;
    
    $fname = SBD_ROOT.'/others/dse.htm';
    
    $html = file_get_html($fname);

    $rows = $html->find('tr');
    foreach($rows as $row)
    {
    	$ticker = trim($row->childNodes(1)->plaintext); //ticker
    	$ltp  = trim($row->childNodes(2)->plaintext); // LTP
    	$high = trim($row->childNodes(3)->plaintext); // high
    	$low  = trim($row->childNodes(4)->plaintext); //low
        $clos  = trim($row->childNodes(5)->plaintext); //close
    	$vol  = trim($row->childNodes(9)->plaintext); // vol
    	$ycl  = trim($row->childNodes(6)->plaintext); // yesterday close
    	
    	if(strpos($ticker, "Trading Code") || $ticker =="") continue;
    	if(preg_match("/\bT05Y/i", $ticker)) continue;
    	if(preg_match("/\bT10Y/i", $ticker)) continue;
    	if(preg_match("/\bT15Y/i", $ticker)) continue;
    	if(preg_match("/\bT20Y/i", $ticker)) continue;
    	if(preg_match("/\bT5Y/i", $ticker)) continue;
    	if(preg_match("/\bDEB/i", $ticker)) continue;
    	
    	$snapshot_ds[$ticker]=array("lt" => $ltp, "hi" => $high, "lo"=>$low, "cl" => $clos, "ycl" => $ycl, "vl"=>$vol);
    }
}      

//finds the delta of two shanpshot
function find_delta()
{
     global $snapshot_del, $snapshot_ds, $snapshot_gq;
     
     if(count($snapshot_gq)==0) return;
     if(count($snapshot_ds)==0) return;
     
     foreach($snapshot_gq as $ticker => $snap)
        {
           if(isset($snapshot_ds[$ticker])) // ticker name match
           {
              $snapshot_del[$ticker]['lt'] = $snapshot_gq[$ticker]['lt']-$snapshot_ds[$ticker]['lt'];      
              $snapshot_del[$ticker]['hi'] = $snapshot_gq[$ticker]['hi']-$snapshot_ds[$ticker]['hi'];      
              $snapshot_del[$ticker]['lo'] = $snapshot_gq[$ticker]['lo']-$snapshot_ds[$ticker]['lo'];      
              $snapshot_del[$ticker]['vl'] = $snapshot_gq[$ticker]['vl']-$snapshot_ds[$ticker]['vl'];      
           }            
        } // for each
}
 
 function save_data()
 {
     global $snapshot_del;
     global $snapshot_ds;
     global $snapshot_gq;
     
    
    if(isset($snapshot_ds)){ 
        //save the dse snapshot
        $filename =  SBD_ROOT.'/others/dse.txt';   
        $fid =  fopen($filename, "w");
        if($fid === false){
            log_err(__FILE, "Error creating file ".$filename);
            exit(0);
        }
       
        foreach($snapshot_ds as $key => $tuple){             
             fprintf($fid, $key.','.implode(",", $tuple));
             fprintf($fid, "\n");
        }  
        fclose($fid);
    }
    
    if(isset($snapshot_gq)){
        //save the delta snapshot
        $filename =  SBD_ROOT.'/others/gqs.txt';   
        $fid =  fopen($filename, "w");
        if($fid === false){
            log_err(__FILE, "Error creating file ".$filename);
            exit(0);
        }
       
       foreach($snapshot_gq as $key => $tuple){             
         fprintf($fid, $key.','.implode(",", $tuple));
         fprintf($fid, "\n");
       }  
       fclose($fid);
    }
    
    if(isset($snapshot_gq)){
        //save the delta snapshot
        $filename =  SBD_ROOT.'/others/gqs2.txt';   
        $fid =  fopen($filename, "w");
        if($fid === false){
            log_err(__FILE, "Error creating file ".$filename);
            exit(0);
        }
       
       foreach($snapshot_gq as $key => $tuple){  
         if($tuple['op']==$tuple['hi']){
            fprintf($fid, $key.','.$tuple['vl']);
            fprintf($fid, "\n");    
         }           
       }  
       fclose($fid);
    }
    
    if(isset($snapshot_del)){
        //save the delta snapshot
        $filename =  SBD_ROOT.'/others/diff.txt';   
        $fid =  fopen($filename, "w");
        if($fid === false){
            log_err(__FILE, "Error creating file ".$filename);
            exit(0);
        }
       
       foreach($snapshot_del as $key => $tuple){        	 
    	    fprintf($fid, $key.','.$tuple['vl']);
            fprintf($fid, "\n");
       }  
       fclose($fid);
    }
    
 }
