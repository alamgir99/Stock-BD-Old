<?php

$dir = dirname(dirname(__FILE__)); // location of  root dir
include_once $dir.'/config.php'; // config info
include_once $dir.'/bare.php'; // basic logging functions
// db functions
include_once SBD_ROOT."/prv/basicdb.php";   


$datatable = array(); 
$tdates = array(
			'2011-06-05',
			'2011-06-06',
			'2011-06-07',
			'2011-06-08',
			'2011-06-09',
			'2011-06-12',
			'2011-06-13',
			'2011-06-14',
			'2011-06-15',
			'2011-06-16',
			'2011-06-19',
			'2011-06-20',
			'2011-06-21',
			'2011-06-22',
			'2011-06-23',
			'2011-06-26',
			'2011-06-27',
			'2011-06-28',
			'2011-06-29',
			'2011-06-30');
			
$sectors = array();
$sids = array();
$facevalues = array();
$sec_facevalues = array();
$datatable = array();

//start code
$time_start = microtime_float();
read_sectors();
read_facevalues();
find_sector_facevalues();

foreach($tdates as $tday){
//process the file and save
process_csv($tday);
//update sector data
update_sector_data($tday);
}

$time_end = microtime_float();
$time_el = $time_end - $time_start;

echo "MST grabbed, converted to CSV. Took $time_el seconds.";
 exit(1);

// supporting functions are below   

  // this function processes the mst file
function process_csv($tday)
{
    global $datatable;    // need to access to save as csv
        
   $filename = SBD_ROOT.'/data/csvdata/2011/dse-'.$tday.'.csv'; 
    $fid = fopen($filename, 'r');
    if($fid === false){
	echo 'Failed to open file: '.$filename; 
	return;
   }
	    
    //read the file and close it
    $buffer = fread($fid,filesize($filename));
    fclose($fid);
    
        
   $buffer = explode("\n", $buffer);
  
   foreach($buffer as $buf){
      $buf = explode(',',$buf);
      $datatable[$buf[0]]['cl'] = $buf[5]; 
      $datatable[$buf[0]]['vl'] = $buf[6]; 
   }
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
function update_sector_data($tday)
{
   global $datatable;
   global $sectors, $sids;
   global $db;
   
   //find total turnover
   $tot_to = 0;
   $sect_to = array();
   $sect_nto = array();
      
   foreach($sectors as $sector => $tickers){
       foreach($tickers as $tick){
           $sect_to[$sector] += ($datatable[$tick]['cl']*$datatable[$tick]['vl'])/1e6;        
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
        $sect_gn = 0;
        $sect_ls = 0;
        $sect_un = 0;
        $to = sprintf("%01.4f",$sect_to[$sector]);
        $nto = sprintf("%01.2f",$sect_nto[$sector]);
                              
        $sql = 'INSERT INTO sector_data(tdate, s_id, turnover, nturnover, gainer, looser, unchanged) VALUES(';
        $sql = $sql .'\''.$tday.'\','.$sid.','.$to.','.$nto.','.$sect_gn.','.$sect_ls.','.$sect_un.')';
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
