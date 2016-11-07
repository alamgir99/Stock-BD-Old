<?php

$dir = dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";


  //do download
if(isset($_GET['date_from']))
    $tdate_from =$_GET['date_from']; // starting date
else
    $tdate_from = date("Y-m-d");

if(isset($_GET['date_to']))
    $tdate_to =$_GET['date_to']; // ending date
else
    $tdate_to = date("Y-m-d");

if(isset($_GET['data_type']))
    $data_type = $_GET['data_type']; // type of data

if(isset($_GET['submit']))
     $submit = $_GET['submit']; // submit pressed
else
    $submit = false;     

//    echo  $tdate_from.' -> '.$tdate_to;
//an array containing all the dates in the range    
$tdates = date_range($tdate_from, $tdate_to);
//echo  $tdates;
 
 //echo implode(';', $tdates);
 //echo $data_type;
 //exit;
  
 
if ($submit=='Get Data'){ // we are downloading right now
    switch($data_type){
    case  'csv' :
        $fname = prepare_download($tdates, 'csv');
        //emit $fname for browser downlad
        force_download($fname);
       
        break;
    case 'mst':
        $fname = prepare_download($tdates, 'mst');
        //emit $fname for browser downlad
        force_download($fname);
       
        break;
    case 'min':
        $fname = prepare_download($tdates, 'min');
       //emit $fname for browser downlad
        force_download($fname);
       
        break;
    }
  //  no need to show the download form
  exit;                  
}  




///////////////////////////////
function date_range($from, $to)
{
    $range = array();
    
    $from = str_replace(':','-',$from);    
    $to = str_replace(':','-',$to);

    if($from==$to)
        return $from;
        
    if (is_string($from) === true) $from = strtotime($from);
    if (is_string($to)   === true) $to   = strtotime($to);
    
    if($from > $to){
        $temp = $from; $from = $to; $to=$from;
    }
    
    do{
        $range []= date('Y-m-d', $from);
        $from = strtotime('+ 1 day', $from);
    } while ($from <= $to);
    return $range;
}

//////
function prepare_download($dates, $kind)
{
    
    //include the zip libary
    require_once(SBD_ROOT.'/pub/pclzip.lib.php');
    //require_once('pcltrace.lib.php');
    //require_once('pclzip-trace.lib.php');
  
   // PclTraceOn(2);
    
    $paths = array();
    $rndstr = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',5)),0,5);
    
    
    switch($kind){
        case 'csv':
        $arch_path = SBD_ROOT.'/prv/temp/csvdata_';
        $arch_path = $arch_path .$rndstr.'.zip';
        $archive = new PclZip($arch_path);
       
        if(is_array($dates)===FALSE) // a single day
        {
            $dat_yr = substr($dates, 0, 4);
            $paths = SBD_ROOT.'/'.'data/csvdata/'.$dat_yr.'/dse-'.$dates.'.csv';
            return $paths;
        }
        
        //for multiple file
        foreach($dates as $dat)
        {
            $dat_yr = substr($dat, 0, 4);
            $path =  SBD_ROOT.'/'.'data/csvdata/'.$dat_yr.'/dse-'.$dat.'.csv';
            if(file_exists($path))
                $paths []= $path;
        }
        //echo implode(';', $paths);
        //exit;
        
        $v_list = $archive->create($paths, PCLZIP_OPT_REMOVE_ALL_PATH);
        
       //cho implode(';', $v_list);
        
        if($v_list == 0){
      //      echo "error creating zip\n";
            return false;
        }
        else{
            return $arch_path;
        }
        
        break;
       
        case 'mst':
        $arch_path = SBD_ROOT.'/prv/temp/mstdata_'.$rndstr.'.zip';
        $archive = new PclZip($arch_path);
        
        if(is_array($dates)===FALSE) // a single day      
        {
            $dat_yr = substr($dates, 0, 4);
            $paths = SBD_ROOT.'/data/mstdata/'.$dat_yr.'/mst-'.$dates.'.txt';
            return $paths;
        }
        
        foreach($dates as $dat)
        {
            $dat_yr = substr($dat, 0, 4);
            $path =  SBD_ROOT.'/data/mstdata/'.$dat_yr.'/mst-'.$dat.'.txt';
            if(file_exists($path))
                $paths []= $path;
        }
        $v_list = $archive->create($paths, PCLZIP_OPT_REMOVE_PATH, SBD_ROOT.'/prv');
        if($v_list == 0){
            return false;
        }
        else{
            return $arch_path;
        }
        
        break;         
        
        case 'min':
        if(is_array($dates)===FALSE) // a single day      
        {
            $dat_yr = substr($dates, 0, 4);
            $path = SBD_ROOT.'/data/mindata/'.$dat_yr.'/dse-intra-'.$dates.'.zip'; 
            return $path;
        }
        else{
             
             include SBD_ROOT.'/pub/include/msginc.php';
             message('Please choose a single date.'); 
             exit(0);
        }     
        break;
    }
}

function force_download($fname)
{
    //http://elouai.com/force-download.php
    
    // required for IE, otherwise Content-disposition is ignored
    if(ini_get('zlib.output_compression'))
        ini_set('zlib.output_compression', 'Off');
  
    if(file_exists($fname))
    {
        $ext = pathinfo($fname, PATHINFO_EXTENSION);
        if($ext =='zip') 
            $ctype ='application/zip';
        else
            $ctype = 'application/csv';
            
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false); // required for certain browsers 
        header("Content-Type: $ctype");

        header('Content-disposition: attachment; filename='.basename($fname));
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($fname));
        readfile($fname);
        
        if($ext =='zip')
            unlink($fname);  // delete the fime
     }
     else //file does not exist
     {
         include SBD_ROOT.'/pub/include/msginc.php';
         message('Data for the date(s) are not found.'); 
         exit(0);
     }
}

