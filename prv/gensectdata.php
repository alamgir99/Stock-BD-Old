<?php
// generates sectoral data from current snapshot
// run by cron every 10-15 mins or so

$dir = dirname(dirname(__FILE__)); // location of  root dir
include_once $dir.'/config.php'; // config info

if($sb_market_open === false)
exit(0);

include_once $dir.'/bare.php'; // basic logging functions
// db functions
include_once SBD_ROOT."/prv/basicdb.php";  

//DOM parser class
include_once(SBD_ROOT.'/prv/simple_html_dom.php');
    
    
$sectors = array(); // ticker names
$sids = array();  // sector ids
$sector_titles = array();
$gainerloosers = array(); // gainer looser info for each symbol
$sector_data = array(); // sectoral data to, norm to, gainer, looser, unchanged
$snapshot = array(); // contains the trade hour data


//start time
$time_start = microtime_float();

get_data_from_dse();

read_sectors();
cal_index_to();
cal_sector_data();
save_data();
cal_top_stock_sector();

$time_end = microtime_float();
$time_el = $time_end - $time_start;
$time_el = sprintf("%01.2f", $time_el);
log_msg(__FILE__, "Sectoral data updated, took $time_el seconds");
exit(0);

//supporting functions below


function get_data_from_dse(){
    
    $letters = array('A','B','C', 'D','E', 'F', 'G', 'H', "I", 'J','K','L','M','N',
                     'O','P','Q','R','S','T','U','V','W','X','Y','Z','1');
    
    foreach($letters as $letter) {
        $fname = SBD_ROOT.'/prv/temp/dsesnapalpha-'.$letter.'.htm';
        $cmd = 'curl http://dsebd.org/latest_share_price_alpha.php?letter='.$letter.'  --connect-timeout 25 --max-time 45 -o ' .$fname.' -e http://dsebd.org/latest_share_price_alpha.php'; 
        $err_code = exec ($cmd);
        
        process_snap_file($fname);
    }
}


//process a snap file
function process_snap_file($fname)
{
    global $snapshot;
     
     $fid = fopen($fname, 'r');
     if($fid == null) {
        log_msg(__FILE__, 'Failed to open file: '.$filename); 
        return;
     }    

     //read the file and close it
     $buffer = fread($fid,filesize($fname));
     fclose($fid);
     
     $buffer = preg_replace("/[\r\n\t]+/","", $buffer); 
     $bpos = strpos($buffer, "Trading Code");
     $bpos = strpos($buffer, "<table", $bpos-1800);
     $epos = strpos($buffer, "</table>",$bpos);
     
     $buffer = substr($buffer, $bpos, $epos-$bpos+8); 
     $html = str_get_html($buffer);

    $rows = $html->find('tr');
    foreach($rows as $row)
    {
        $ticker = trim($row->childNodes(1)->plaintext); //ticker
        $ltp  = trim($row->childNodes(2)->plaintext); // LTP
        $ycp  = trim($row->childNodes(6)->plaintext); //ycp
        $chg  = trim($row->childNodes(7)->plaintext); //chanage 
        $vol  = trim($row->childNodes(9)->plaintext); // vol
        $val  = trim($row->childNodes(10)->plaintext); // val
        
        
        if($ticker=="Trading Code" || $ticker =="") continue;
        if(isset($snapshot[$ticker])) return; // no need to process this file altogether
        
        if(preg_match("/\bT05Y/i", $ticker)) continue;
        if(preg_match("/\bT10Y/i", $ticker)) continue;
        if(preg_match("/\bT15Y/i", $ticker)) continue;
        if(preg_match("/\bT20Y/i", $ticker)) continue;
        if(preg_match("/\bT5Y/i", $ticker)) continue;
        if(preg_match("/\bDEB/i", $ticker)) continue;
        
        $chgp = $chg*100/$ycp;
        $chgp = sprintf("%01.2f", $chgp);
        
        $snapshot[$ticker]=array("ycp" => $ycp, "ltp" => $ltp, "chg"=>$chg, "chgp"=>$chgp, "vol" => $vol, "val"=>$val);
    }
    
     
      
}

 
// do the sectoral calculation
function cal_sector_data(){
    
    global $sectors;
    global $sector_data;
    global $snapshot;
    
    $total_to = 0;
    
    foreach($sectors as $sector => $tickers){
        $gainer = 0;
        $looser = 0;
        $unchanged = 0;
        $sect_to = 0;
       foreach($tickers as $tick){
           $sect_to += $snapshot[$tick]['val'];
           if($snapshot[$tick]['chg'] > 0)
             $gainer++;
           else if  ($snapshot[$tick]['chg'] < 0)
             $looser++;
           else
            $unchanged++;  
       }
       
       $tot_to += $sect_to;
       $sect_to = sprintf("%01.2f", $sect_to);    
       $sector_data[$sector]['to'] = $sect_to;
       $sector_data[$sector]['nt'] = 0.00; // dummy will fill later on
       $sector_data[$sector]['gn'] = $gainer;
       $sector_data[$sector]['ls'] = $looser;
       $sector_data[$sector]['un'] = $unchanged;
   }
   
   // also insert the normalised to
   foreach($sector_data as $sector => $sd){
       $nor_to = $sd['to']*100/$tot_to;
       $nor_to = sprintf("%01.2f", $nor_to);    
       $sector_data[$sector]['nt'] = $nor_to;
   }
    
}
   
function save_data()
{
    global $sector_data;
    global $snapshot;
    global $sb_tradingend;
    global $sids;
    global $db;
    
    //time instant of capture
    $time_sig = date("H:i:s"); 
    $date_sig = date('Y-m-d');
    $date_yr= date("Y");
   
    $wd = SBD_ROOT.'/data/secdata/'.$date_yr.'/'.$date_sig;
        //no delta yet, then nothing to proceed
    if(count($sector_data)==0) return;
    
    //now save one file for each ticker
    foreach($sector_data as $sector => $val){
        $filename = $wd .'/'.$sector.'.txt';
        $fid = fopen($filename, 'a');
        if($fid !== false){ // created ok
            fprintf($fid, $time_sig.','.implode(',', $val));
            fprintf($fid, "\n");
            fclose($fid);            
        }
    }
  /*  
     $wd = SBD_ROOT.'/data/secdata/'.$date_yr.'/'.$date_sig
     $fid = fopen($wd.'/00DSEGEN.txt','a');
     if($fid !== false){ // created ok
            fprintf($fid, $time_sig.','.$snapshot['00DSEGEN']['ltp'].','.$snapshot['00DSEGEN']['vol']);
            fprintf($fid, "\n");
            fclose($fid);            
        }

     $fid = fopen($wd.'/00DSETO.txt','a');
     if($fid !== false){ // created ok
            fprintf($fid, $time_sig.','.$snapshot['00DSETO']['ltp'].','.$snapshot['00DSETO']['val']);
            fprintf($fid, "\n");
            fclose($fid);            
        }
*/	
	$timenow = date("G");
	if($timenow >= $sb_tradingend ) // trading hour is over
	{ // save the sector data into the DB
		foreach($sector_data as $sector => $data)
		{
			$sid = $sids[$sector];
			$tdate  =$date_sig ;
			$to  = $data['to'];
			$nto = $data['nt'];
			$gn  = $data['gn'];
			$ls   = $data['ls'];
			$un = $data['un'];
			
			$sql  = 'INSERT INTO sector_data(tdate, s_id, turnover, nturnover, gainer, looser, unchanged) VALUES(';
			$sql .= '"'.$tdate.'",'.$sid.','.$to.','.$nto.','.$gn.','.$ls.','.$un.')';
			
			$db->query($sql);
		}
	}
}     

  
function read_sectors()
{
    global $sectors;
    global $sector_titles; 
    global $db;
    global $sids;
    
    $sids = array();
    
     $sector_names = array();
     //add sectoral tickers
     
     $sql = 'SELECT s_id, sector_name, title FROM sectors';
     $res = $db->query($sql);
     $i = 0;
     if($res){
         while($data = $db->fetch_row($res))
         {
             $sids[$data[1]]= $data[0];
             $sector_names[$i]=$data[1];
             $sector_titles[$data[1]]=$data[2];
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


function cal_index_to()
 {
     global $snapshot;
     global $firstsnap;
          
     
     $fname = SBD_ROOT.'/prv/temp/indexedstocks.txt';    
     $fid = fopen($fname, "r");   
     if($fid===false){
         log_msg(__FILE__, "Can't read file ".$fname);
         return;
     }
     fclose($fid);
     
     $temp = file_get_contents($fname);
     $temp = preg_split("/[\r\n]/", $temp);
     
     foreach($temp as $pair){
        $pair = explode(',', $pair);
        $indexed_stock[$pair[0]]= $pair[1];
     }
  

     //read all yesterday close
     $fname = SBD_ROOT.'/prv/temp/dgencap.php';
     //on first snap
     if(file_exists($fname)===false) {
         $fname = SBD_ROOT.'/data/yclose.txt';    
         $fid = fopen($fname, "r");   
         if($fid===false){
            log_err(__FILE__, "Can't read file ".$fname);
            exit;
         }
         
         $buffer = fread($fid, filesize($fname));
         $buffer = explode("\n", $buffer);
         fclose($fid);
  
         foreach($buffer as $buf){
            $buf = explode(',',$buf);
            $yclose[trim($buf[0])] = trim($buf[1]); 
         }
         
         if(isset($yclose['00DSEGEN']))
            $dsegen_op = $yclose['00DSEGEN'];
         else
            $dsegen_op = 0; 
            
         //find opening mcap
         $mcap_op  = 0;
         foreach($indexed_stock as $tick => $cnt){
             $mcap_op  += ($cnt * $yclose[$tick])/1e6;
         }
         
         //now write these two values for subsequent index calculation
         $fname = SBD_ROOT.'/prv/temp/dgencap.php';
         $content = '<?php $dsegen_op='.$dsegen_op.'; $mcap_op='.$mcap_op.';';
         file_put_contents($fname, $content);    
         
        // $snapshot["00DSEGEN"]=array("ycp" => $dsegen_op, "ltp" => $dsegen_op, "chg"=> 0, "vol" => 0, "val"=>0);
        // $snapshot["00DSETO"]=array("ycp" => $dsegen_op, "ltp" => $dsegen_op, "chg"=> 0, "vol" => 0, "val"=>0);      
          
         return; // nothin to do further
     }
  
     
     $fname = SBD_ROOT.'/prv/temp/dgencap.php';
     if(file_exists($fname)){
         include $fname;  // this will give us $dsegen_op and $mcap_op vars
     }
     $mcap_cur = 0;
     
     foreach($indexed_stock as $tick => $cnt){
         $mcap_cur += ($cnt * $snapshot[$tick]['ltp'])/1e6;
     }
         
     //calculate the current index
     if($mcap_cur != 0){
        $dsegen = $dsegen_op*$mcap_cur/$mcap_op;
        $dsegen = sprintf("%01.2f", $dsegen);
     }
     
     $chg = ($dsegen - $dsegen_op)*100/$dsegen_op;
     $chgp = sprintf("%01.2f", $chg);
         
     $turnover = 0;
     $totvol = 0;
     foreach($snapshot as $sn)
     {
         $turnover += $sn['val'];  
         $totvol   += $sn['vol'];
     }
     
     $dsevol = $totvol; 
     $dseto  = sprintf("%01.3f", $turnover); // in millions 
     
     //insert the inde and vol ticker
     $snapshot["00DSEGEN"] = array("ycp" => $dsegen_op, "ltp" => $dsegen, "chg"=> $chg, "chgp"=> $chgp, "vol" => $totvol, "val"=> $totvol);
     $snapshot['00DSETO']  = array("ycp" => $dsegen_op, "ltp" => $dsegen, "chg"=> $chg, "chgp"=> $chgp, "vol" => $dseto, "val"=> $dseto);
 }
 
 //pick 5 top stocks and sectors
function cal_top_stock_sector()
{
    global $snapshot;
    global $sector_data;
    global $sector_titles;
    
    $totalvol = $snapshot['00DSEGEN']['vol'];
    $totalto = $snapshot['00DSETO']['val'];
    
   // pick top 5 stocks that gained/lost/turnover/traded
   $topchange = array();
   $topturnov = array();
   //$topturnvl = array();
   
   foreach($snapshot as $key => $val)
   {
	if($key=='00DSEGEN' || $key == '00DSETO') continue;
	
       $topchange[$key] = $val['chgp'];
       $topturnov[$key] = $val['val'];
       //$topturnvl[$key] = $val['vol'];
   }
   
   arsort($topchange);
   $stock_top5g = array();
   $cnt = 0;
   foreach($topchange as $key => $val)
   {
       if($cnt == 5 || $val < 0) break;
       $stock_top5g[$key]['ltp'] = $snapshot[$key]['ltp'];
       $stock_top5g[$key]['chg'] = $snapshot[$key]['chg'];
       $stock_top5g[$key]['chgp'] = $snapshot[$key]['chgp'];
       $stock_top5g[$key]['vol'] = $snapshot[$key]['vol'];
       $stock_top5g[$key]['val'] = $snapshot[$key]['val'];
       
       $cnt++;
   }
   
   
   asort($topchange);
   $stock_top5l = array();
   $cnt = 0;
   foreach($topchange as $key => $val)
   {
       if($cnt == 5 || $val > 0) break;
       $stock_top5l[$key]['ltp'] = $snapshot[$key]['ltp'];
       $stock_top5l[$key]['chg'] = $snapshot[$key]['chg'];
       $stock_top5l[$key]['chgp'] = $snapshot[$key]['chgp'];
       $stock_top5l[$key]['vol'] = $snapshot[$key]['vol'];
       $stock_top5l[$key]['val'] = $snapshot[$key]['val'];
       $cnt++;
   }
   
   $topturnov['00DSEGEN'] = 0;
   $topturnov['00DSETO'] = 0;
   arsort($topturnov);
   $stock_top5t = array();
   $cnt = 0;
   foreach($topturnov as $key => $val)
   {
       if($cnt == 5 || $val == 0) break;
       $stock_top5t[$key]['val'] = $val;
       $stock_top5t[$key]['valp'] = sprintf("%01.2f",$val*100/$totalto);
       $stock_top5t[$key]['ltp'] = $snapshot[$key]['ltp'];
       $stock_top5t[$key]['chg'] = $snapshot[$key]['chg'];
       $stock_top5t[$key]['chgp'] = $snapshot[$key]['chgp'];
       
       
       $cnt++;
   }
   
   $fname = SBD_ROOT.'/pub/stock5.htm';
   $fid = fopen($fname, "w");
   if($fid){
       ob_start();
       ?>
       <html><head><title></title>
       <style type="text/css">
       body{
           font-size: 8px;
       }
       table {
        border-collapse: collapse; 
        border: 1px solid gray; 
       }
       td {
        border-width: 1px; 
        border-style: dotted dotted;
        padding:3px;
        text-align: center;
        font-size: 10pt;
        margin: 0px;
       }
       </style>
       </head><body>
       <table>
       <tr><td>Gainer:</td> <?php 
                                 foreach($stock_top5g as $tick => $val){
                                     $tip = 'LTP: '.$val['ltp'].' Tk, '.'Vol: '.$val['vol'].', Val: '.$val['val'].'mn';
                                     echo '<td title="'.$tip.'">'.$tick.'<br />'.$val['chg'].' ('.$val['chgp'].'%)</td>';
                                 }
                                 for($i=count($stock_top5g); $i<5; $i++){ // empty data to make up the table
                                     echo '<td>&nbsp;</td>';
                                 }    
                                 ?> 
       </tr>
       <tr>
       <td>Looser:</td>    <?php 
                                 foreach($stock_top5l as $tick => $val){
                                     $tip = 'LTP: '.$val['ltp'].' Tk, '.'Vol: '.$val['vol'].', Val: '.$val['val'].'mn';
                                     echo '<td title="'.$tip.'">'.$tick.'<br />'.$val['chg'].' ('.$val['chgp'].'%)</td>';
                                 }
                                 for($i=count($stock_top5l); $i<5; $i++){ // empty data to make up the table
                                     echo '<td>&nbsp;</td>';
                                 }    
                                 ?> 
       </tr>
       <tr>
       <td title="Contribution to the DSEGEN">Idx Cont:</td> <?php 
                                 for($i=1; $i<=5; $i++){ // empty data to make up the table
                                     echo '<td>&nbsp;</td>';
                                 }    
                                 ?> 
       </tr>
       <tr>
       <td>Turnover:<br /> (mn)</td>   <?php 
                                 foreach($stock_top5t as $tick => $val){
                                     $tip = 'LTP: '.$val['ltp'].' Tk, '.'Chng: '.$val['chg'].'Tk ('.$val['chgp'].'%)';
                                     echo '<td title="'.$tip.'">'.$tick.'<br />'.$val['val'].' ('.$val['valp'].'%)</td>';
                                 }
                                 for($i=count($stock_top5t); $i<5; $i++){ // empty data to make up the table
                                     echo '<td>&nbsp;</td>';
                                 }    
                                 ?> 
       </tr></table></body></html>
       <?
        $buf = ob_get_clean();
        fwrite($fid, $buf);
        fclose($fid);
   }

   
   // pick top 5 sectors that gained/lost/turnover/traded
   // $sector_data[$sector] = ('to', 'nt', 'gn','ls','un')
   
   $netgainer = array();
   $topturnov = array();
   foreach($sector_data as $sector => $data){
       $netgainer[$sector] = $data['gn']-$data['ls'];
       $topturnov[$sector] = $data['to'];
   }    
   
   arsort($netgainer);
   
   $sect_top5g = array();
   $cnt = 0;
   foreach($netgainer as $key => $val)
   {
       if($cnt == 5 || $val == 0) break;
       $sect_top5g[$key]['gn'] = $sector_data[$key]['gn'];
       $sect_top5g[$key]['ls'] = $sector_data[$key]['ls'];
       $sect_top5g[$key]['to'] = $sector_data[$key]['to'];
       $sect_top5g[$key]['nt'] = $sector_data[$key]['nt'];
       
       $cnt++;
   }
    
    
   asort($netgainer); 
   $sect_top5l = array();
   $cnt = 0;
   foreach($netgainer as $key => $val)
   {
       if($cnt == 5 || $val == 0) break;
       $sect_top5l[$key]['gn'] = $sector_data[$key]['gn'];
       $sect_top5l[$key]['ls'] = $sector_data[$key]['ls'];
       $sect_top5l[$key]['to'] = $sector_data[$key]['to'];
       $sect_top5l[$key]['nt'] = $sector_data[$key]['nt'];
       $cnt++;
   }
   
   arsort($topturnov);
   $sect_top5t = array();
   $cnt = 0;
   foreach($topturnov as $key => $val)
   {
       if($cnt == 5 || $val == 0) break;
       $sect_top5t[$key]['val'] = $sector_data[$key]['to'];
       $sect_top5t[$key]['valp'] = $sector_data[$key]['nt'];
       $sect_top5t[$key]['gn'] = $sector_data[$key]['gn'];
       $sect_top5t[$key]['ls'] = $sector_data[$key]['ls'];
       $cnt++;
   }
   
   $fname = SBD_ROOT.'/pub/sect5.htm';
   $fid = fopen($fname, "w");
   if($fid){
       ob_start(); 
       ?>
       <html><head><title></title>
       <style type="text/css">
       body{
           font-size: 8px;
       }
       table {
        border-collapse: collapse; 
        border: 1px solid gray; 
       }
       td {
        border-width: 1px; 
        border-style: dotted dotted;
        padding:3px;
        font-size: 10pt;
        text-align: center;
        margin: 0px;
       }
       </style>
       </head><body>
       <table>
       <tr><td>Gainer:</td> <?php 
                                 foreach($sect_top5g as $tick => $val){
                                     $tip = 'Val: '.$val['to'].'mn, ('.$val['nt'].'%)'; 
                                     echo '<td title="'.$tip.'">'.$sector_titles[$tick].'<br />+'.$val['gn'].'/-'.$val['ls'].'</td>';
                                 }
                                 for($i=count($sect_top5g); $i<5; $i++){ // empty data to make up the table
                                     echo '<td>&nbsp;</td>';
                                 }    
                                 ?> 
       </tr>
       <tr>
       <td>Looser:</td>    <?php 
                                 foreach($sect_top5l as $tick => $val){
                                    $tip = 'Val: '.$val['to'].'mn, ('.$val['nt'].'%)';  
                                    echo '<td title="'.$tip.'">'.$sector_titles[$tick].'<br />+'.$val['gn'].'/-'.$val['ls'].'</td>';
                                 }
                                 for($i=count($sect_top5l); $i<5; $i++){ // empty data to make up the table
                                     echo '<td>&nbsp;</td>';
                                 }    
                                 ?> 
       </tr>
       <tr>
       <td title="Contribution to the DSEGEN">Idx Cont:</td> <?php 
                                 for($i=1; $i<=5;$i++){ // empty data to make up the table
                                     echo '<td>&nbsp;</td>';
                                 }    
                                 ?> 
       </tr>
       <tr>
       <td>Turnover:<br /> (mn)</td>   <?php 
                                 foreach($sect_top5t as $tick => $val){
                                    $tip = 'Gainer: '.$val['gn'].', Looser: '.$val['ls'];  
                                    echo '<td title="'.$tip.'">'.$sector_titles[$tick].'<br />'.$val['val'].' ('.$val['valp'].'%)</td>';
                                 }
                                 for($i=count($sect_top5t); $i<5; $i++){ // empty data to make up the table
                                     echo '<td>&nbsp;</td>';
                                 }    
                                 ?> 
       </tr></table></body></html>  
       <?
        $buf = ob_get_clean();
        fwrite($fid, $buf);
        fclose($fid);
   }     
}
