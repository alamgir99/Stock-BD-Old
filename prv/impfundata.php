<?php

/* 

Grabs fund data from DSE site 
for all actively traded ticker symbol  
  */
//---------------------------------------------  

//include db settings
require_once "../config.php";

// db functions
require_once SBD_ROOT."/pub/include/common.php";   

//include the DOM parser
include_once(SBD_ROOT.'/prv/simple_html_dom.php');
 /*
$csvfile = SBD_ROOT.'/data/tickers.csv'; 

//read the entire file and get the list of symbols
//check if the file exists
if(!file_exists($csvfile)) {
    log_msg(__FILE__, "Not found: ".$csvfile);
    exit;
}

//open the file
$fileptr = fopen($csvfile,"r");
if(!$fileptr) {
   log_msg(__FILE__,"Error opening:".$csvfile);
   exit;
}

$fsize = filesize($csvfile);
if(!$fsize) {
   log_msg(__FILE__,"File is empty:".$csvfile);
   exit;
}

//read the file content
$csvcontent = fread($fileptr,$fsize);
//now close the file
fclose($fileptr);

$tickers = array();
foreach(explode("\n", $csvcontent) as $line) {
   $line = trim($line," \t");
   $line = str_replace("\r","",$line);
   
        if($line == "") 
        continue;
    
   $linearray = explode(",",$line);
    
   $tickers []= $linearray[0];
}


//insert the tickers
foreach($tickers as $ticker){
    $sql = 'SELECT ticker from sym_list WHERE ticker="'.$ticker.'"';
        
    $result = $db->query($sql);
    if($result) {
            if ($db->num_rows() == 0) {// does not exist so add
                $values = '\''.$ticker.'\',\''.$tdate.'\'';
                $sql = 'INSERT INTO sym_list(ticker, date_added) VALUES('.$values.')';
                $db->query($sql);
            }
        }
        
   //insett into log table
   $sql = 'SELECT ticker from sym_list WHERE ticker="'.$ticker.'"';
        
    $result = $db->query($sql);
    if($result){
        $values = '\''.$ticker.'\',\''.$tdate.'\''.'\'funda bot\'';
        
        if ($db->num_rows() == 0) {// does not exist so add
            $sql = 'INSERT INTO comp_log(ticker, last_update, comment) VALUES('.$values.')';
            $db->query($sql);
        }
        else{
            $sql = 'UPDATE comp_log SET last_update=\''.$tdate.'\'  comment=\'funda bot\'';
            $db->query($sql);
        }
    }     
        
}
$db->free_result();

unset($tickers);
*/
//get the symbol list from the DB 

$sql = 'SELECT ticker from sym_list'; 
$result = $db->query($sql);
if($result) {
   while($tick = $db->fetch_row($result)){
          $tick = (string)$tick[0];
          if(substr($tick, 0, 2)=="00") // ignore the index/turnover etc tickers
            continue; 
          $tickers [] = $tick;
    }
$db->free_result($result);
}

 
// $tickers = array("1JANATAMF","GENNEXT","STYLECRAFT");       
        
/* global array to hold comp information */
$cname    = array();  //names of companies
$minfo    = array();  //market info
$binfo    = array();  //basic info
$lagm     = array();  //last agm
$intfin   = array();  //interim financials
$profstat = array();  //profit status
$peratio  = array();  //audited p/e ratio
$fperm1   = array();  //financial performance
$fperm2   = array();  //further financial performance
$oinfo    = array();  //other info
        
 
 //$tickers = array('MJLBD');
 
//now for each symbol, collect funda data

for($i=0; $i<count($tickers); $i++) 
 {
     
    $ticker = $tickers[$i];
    echo "\n Processing :".$ticker;
    $fname = SBD_ROOT.'/prv/temp/tick'.$i.'-fund.htm'; 
    $cmd = 'curl http://dsebd.org/displayCompany.php?name='.urlencode ($ticker).'  -o  '.$fname .' -e http://dsebd.org/company%20listing.php'; 
    $err_code = exec ($cmd);    
    
    //init the global vars
    $cname    = array();  //names of companies
    $minfo    = array();  //market info
    $binfo    = array();  //basic info
    $lagm     = array();  //last agm
    $intfin   = array();  //interim financials
    $profstat = array();  //profit status
    $peratio  = array();  //audited p/e ratio
    $fperm1   = array();  //financial performance
    $fperm2   = array();  //further financial performance
    $oinfo    = array();  //other info

    //now parse the htm file and store the data
    process_compinfo($ticker, $fname);
    unlink($fname); // delete the file
    
    //now save the info into database
    
    //basic company info -- starts
    $comp_info = array();
    $comp_info ['cs']= $ticker;  // company symbol
    $comp_info ['cn']= $cname;   // company name
    $comp_info ['ac']= $binfo['Authorized Capital in BDT (mn)'];
    $comp_info ['pc']= $binfo['Paid-up Capital in BDT (mn)']; 
    $comp_info ['ts']= $binfo['Total no. of Securities']; // total number of shares 
    $comp_info ['bs']= $binfo['Business Segment']; // business segment
    $comp_info ['fv']= $binfo['Face Value']; // face value
    $comp_info ['ls']= $binfo['Market Lot']; // lot size
    $comp_info ['ly']= $oinfo['Listing Year']; // listing year
    $comp_info ['ye']= $lagm['Year End']; // financial year ending
    $comp_info ['mc']= $oinfo['Market Category']; // market category
    if($binfo['Business Segment']=='Mutual Funds')
        $comp_info ['ty']= 'MF'; // mutual fund
    else if($binfo['Business Segment']=='Corporate Bond')
        $comp_info ['ty']= 'CB'; // corporate bond
    else
        $comp_info ['ty']= 'EQ'; // equity
    $comp_info ['cm']= $oinfo['Remark']; // comment
    
   // foreach($comp_info as $key => $val){
   //     $comp_info[$key]=mysql_real_escape_string($val);
   // }
    
    print_r($comp_info);
    
    $sql = 'SELECT ticker from comp_info WHERE ticker="'.$ticker.'"';
    $result = $db->query($sql);
    if($result) {
            if ($db->num_rows() == 0) {// does not exist so add
                $values = '\''.implode('\',\'', $comp_info).'\''; 
                $sql = 'INSERT INTO comp_info(ticker, name, auth_cap, paid_cap, tot_sec, segment, face_value, lot_size, list_year, year_end, category, type, comment)';
                $sql = $sql .' VALUES('.$values.')';
                $db->query($sql);
            }
             else{ // update
               $sql = 'UPDATE comp_info SET name=\''.$comp_ino['cn'] .'\' segment=\''.$comp_info['bs'].'\' face_value=\''.$comp_info['fv'];
               $sql = $sql . '\' lot_size=\''.$comp_info['ls'].'\' list_year=\''.$comp_info['ly'].'\' year_end=\''.$comp_info['ye'];
               $sql = $sql . '\' category=\''.$comp_info['mc'].'\' type=\''.$comp_info['ty'].'\' comment=\''.$comp_info['cm'] .'\'WHERE ticker=\''.$ticker.'\'';
               $db->query($sql);
            } 
              
     $db->free_result($result);    
    }
    //basic company info -- ends
    
    //share holding info -- starts
     // by default assume that the holdings did not change since listing
    $hyear = $oinfo['Listing Year'].'-01-01'; // listing year
    $held = parse_holding_string($oinfo['Share Percentage:']);
    
    $sql = 'SELECT ticker from comp_info WHERE ticker="'.$ticker.'"';
    $result = $db->query($sql);
    if($result){
        if($db->num_rows()==0){ // no records exist
              $sql = 'INSERT INTO holdings(ticker, hyear, spondir, gov, inst, forgn, pub)  VALUES(\'';
              $sql = $sql . $ticker .'\',\''.$hyear.'\',\''.$held['sd'].'\',\''.$held['gv'].'\',\''.$held['in'].'\',\''.$held['fr'].'\',\''.$held['pb'].'\')';
              $db->query($sql);
        }
        else{
            $hyear = date('Y-m-d');
            $sql = 'UPDATE holdings SET hyear=\''.$hyear.'\',spondir=\''.$held['sd'].'\',gov=\''.$held['gv'].'\',inst=\''.$held['in'].'\',forgn=\''.$held['fr'].'\',pub=\''.$held['pb'];
            $sql = $sql .'\' WHERE ticker=\''.$ticker .'\'';
            $db->query($sql);
        }
    }
    //share holding info -- ends
    
    // audited finance - starts
    if ($comp_info ['ty']== 'EQ'){ // only valid for EQ 
             foreach($fperm1 as $yr => $fp){ //  for each year's financial performance
                $fp = str_replace('n/a', '0.00', $fp);
                $val1 = explode(',', $fp);
                $fp = str_replace('n/a', '0.00', $fperm2[$yr]);
                $val2 = explode(',', $fp);
                
                //val1 = basic eps, dil eps, basic nav, dil nav, net profit
                list($eps_bs, $eps_dl, $nav_bs, $nav_dl, $net_prof) = $val1;
                
                //val2 = pe, dividend, yield 
                list($pe_bs, $dividend, $yield) = $val2;
                list($stockd, $cashd) = parse_dividend($dividend);
                $pe_dl = '0';
                $sql = 'INSERT INTO aud_fin(ticker, year, eps_bs, eps_dl, pe_bs, pe_dl, c_bonus, s_bonus, yield, nav_bs, nav_dl, net_profit) ';
                $sql = $sql .' VALUES(\''.$ticker.'\',\''.$yr.'\','.$eps_bs.','.$eps_dl.','.$pe_bs.','.$pe_dl.','.$cashd.','.$stockd.','.$yield.','.$nav_bs.','.$nav_dl.','.$net_prof.')';
                $db->query($sql);
             }
      
    }
    //audited finance - ends
    //interim performance
    if ($comp_info ['ty']== 'EQ'){ // only valid for EQ 
          $eps = $intfin['Diluted EPS in BDT (Based on continuing operations)'];
          $eps = str_replace('n/a', '0.00', $eps);
          list($q1, $q2, $q3, $q4) = explode(',',$eps);
          $fyear =  date('Y-m-d'); 
          $sql = 'INSERT INTO interim_fin(ticker, fyear, q1eps, q2eps, q3eps, q4eps) VALUES(\'';
          $sql = $sql . $ticker.'\',\''.$fyear.'\',' .$q1.','.$q2.','.$q3.','.$q4.')';
          $db->query($sql);
    } 
    
 }
 
  echo ' Done';   
  log_msg(__FILE__, "Funda data import success.");
  
 
 // supporting functions below
 
 //parse a comp file html file
function process_compinfo($tick, $fname)
{
    global $cname;
    
    $fid = fopen($fname, 'r');
    if($fid == null) {
        file_put_contents(SBD_ROOT.'/log/log.txt', '\nFailed to open mst file from temp folder.', FILE_APPEND); 
        exit(0);
     }    
      
    $content = fread($fid, filesize($fname));
    fclose($fid);
    $content = html_entity_decode($content);
    $content = preg_replace("/[\s\n\r\t]+/", " ", $content);
    
    // the page has two (nested) HTML blocks. the core info
    // we are looking at is in the second html block
    $bpos = strpos($content, "<html>", 100); // offset 100, we want to igonore the first HTML tag       
    $epos = strpos($content, "</html>", $bpos);
    
    $compinfo = substr($content, $bpos, $epos-$bpos+7); // include the closing </html> tag
    unset($content);       
           
   // file_put_contents("compinfo11.html", $compinfo);
           
    //$html = str_get_html($compinfo);

    /* company info block starts */
    $bpos = strpos($compinfo, "<table border=");
    $epos = strpos($compinfo, "</table>", $bpos);
    $ablock = substr($compinfo, $bpos, $epos - $bpos+8); // get the closing tag
    $ablock = str_replace("\n",'', $ablock);
    $ablock = str_replace('&nbsp;', '', $ablock);   
     
    $html = str_get_html($ablock);
    $ret =trim($html->plaintext);
    //$ret = preg_replace("/[\r\n]+/", '', $ret);
    $info = explode(':', $ret);
    $cname =trim($info[1]);
    /* company info block ends */
    
    /* trading code, comp no - begins */
    $bpos = strpos($compinfo, "<table border=", $bpos+8);
    $epos = strpos($compinfo, "</table", $bpos);
    $ablock = substr($compinfo, $bpos, $epos-$bpos+8);
    // we dont use this block so ignore    
    /* trading code, comp no - ends */
    
    /* market info block - begins */
    $bpos = strpos($compinfo, "<table border=", $bpos+8); // outer
    $bpos = strpos($compinfo, "<table", $bpos+8); // inner left
    $epos = strpos($compinfo, "</table>", $bpos);
    $epos = strpos($compinfo, "</table>", $epos+8); // go past the change table
    $lblock = substr($compinfo, $bpos, $epos - $bpos+8); // get the closing tag
    $lblock = str_replace("\n",'', $lblock);
    $lblock = str_replace('&nbsp;', '', $lblock);   
    
    $bpos = strpos($compinfo, "<table", $epos); // inner right
    $epos = strpos($compinfo, "</table>", $bpos);
    $rblock = substr($compinfo, $bpos, $epos - $bpos+8); // get the closing tag
    $rblock = str_replace("\n",'', $rblock);
    $rblock = str_replace('&nbsp;', '', $rblock);
    
    // now process $lblock and $rblock
    // not sure about its use
    process_block($tick, $lblock, 'minfol');
    process_block($tick, $rblock, 'minfor');
    /* market info block - ends */
    
    /* basic info block - begins */
    $bpos = strpos($compinfo, "<table border=", $epos); // outer
    $bpos = strpos($compinfo, "<table border=", $bpos+8); // inner 
    $epos = strpos($compinfo, "</table>", $bpos);
    $ablock = substr($compinfo, $bpos, $epos - $bpos+8); // get the closing tag
    $ablock = str_replace("\n",'', $ablock);
    $ablock = str_replace('&nbsp;', '', $ablock);   
    // process the basic info block, we need  this
    process_block($tick, $ablock, 'binfo'); // processed info are stored in global
    /* basic info block - ends */
    
    /* last AGM info block - begins */
    $bpos = strpos($compinfo, "<table border=", $epos); // outer
    $bpos = strpos($compinfo, "<table border=", $bpos+8); // inner 
    $epos = strpos($compinfo, "</table>", $bpos);
    $ablock = substr($compinfo, $bpos, $epos - $bpos+8); // get the closing tag
    $ablock = str_replace("\n",'', $ablock);
    $ablock = str_replace('&nbsp;', '', $ablock);   
    // process the last AGM block, we need  this
     process_block($tick, $ablock, 'lagm'); // processed info are stored in global
    /* last AGM block - ends */
    
    /* interim financial info block begins */
    $bpos = strpos($compinfo, "<table border=", $epos); // header
    $bpos = strpos($compinfo, "<table border=", $bpos+8); // data table
    $bpos = strpos($compinfo, "<table width=", $bpos+8); // inner table 
    $epos =  strpos($compinfo, "</table>", $bpos); // int diluted eps
    $epos =  strpos($compinfo, "</table>", $epos+8); // get past the last end
    $epos =  strpos($compinfo, "</table>", $epos+8); // get past the last end
    
    $ablock = substr($compinfo, $bpos, $epos - $bpos+8); // get the closing tag
    //process the table
    process_block($tick, $ablock, 'intfin');
    /* interim financial info block ends */
    
    /* profit status P/E table begins */
    $bpos = strpos($compinfo, "<table width=", $epos); 
    $epos = strpos($compinfo, "</table>", $bpos); 
    $ablock = substr($compinfo, $bpos, $epos - $bpos+8); // get the closing tag
    
    //process the block
    process_block($tick, $ablock, 'pstat');
    /* profit status P/E table ends */
    
    /* Current P/E based on last aud fin table begins */
    $bpos = strpos($compinfo, "<table width=", $epos); 
    $epos = strpos($compinfo, "</table>", $bpos); 
    $ablock = substr($compinfo, $bpos, $epos - $bpos+8); // get the closing tag
    process_block($tick, $ablock, 'cperat');
    /* Current P/E based on last aud fin table ends */
    
   
    /* financial performance info block begins */
    $bpos = strpos($compinfo, "<table border=", $epos); // header
    $bpos = strpos($compinfo, "<table border=", $bpos+8); // data table
    $bpos = strpos($compinfo, "<table border=", $bpos+8); // inner table 
    $epos =  strpos($compinfo, "</table>", $bpos); // 
        
    $ablock = substr($compinfo, $bpos, $epos - $bpos+8); // get the closing tag
    //process the table
     process_block($tick, $ablock, 'fperf1'); 
    /* financial performance info block ends */
 
     /* (cont) financial performance info block begins */
    $bpos = strpos($compinfo, "<table border=", $epos); // comment of last block
    $bpos = strpos($compinfo, "<table border=", $bpos+8); // header
    $bpos = strpos($compinfo, "<table border=", $bpos+8); // data table
    $bpos = strpos($compinfo, "<table border=", $bpos+8); // inner table 
    $epos =  strpos($compinfo, "</table>", $bpos); // 
        
    $ablock = substr($compinfo, $bpos, $epos - $bpos+8); // get the closing tag
    //process the table
     process_block($tick, $ablock, 'fperf2');
    /* (cont) financial performance info block ends */
    
     /* other info block begins */
    $bpos = strpos($compinfo, "<table border=", $epos); // header
    $bpos = strpos($compinfo, "<table border=", $bpos+8); // inner table 
    $epos =  strpos($compinfo, "</table>", $bpos); // 
    $epos =  strpos($compinfo, "</table>", $epos+8); // go past holdings table     
        
    $ablock = substr($compinfo, $bpos, $epos - $bpos+8); // get the closing tag
    //process the table
    process_block($tick, $ablock, 'oinfo');
    /* other info block ends */
    
    
}

//process a block and put it into the global array
function process_block($ticker, $block, $kind)
{
    global $minfo;
    global $binfo;
    global $lagm;
    global $intfin;
    global $profstat;
    global $peratio;
    global $fperm1;
    global $fperm2;
    global $oinfo;
    
    switch($kind){
        case 'minfol':
        $html = str_get_html($block);
        $trows = $html->find('tr'); // find all rows
        for($i=1; $i<count($trows); $i++)
        {
               $row = $trows[$i];
               $key = trim($row->find('td',0)->plaintext);
               $key = preg_replace("'\s+'", " ", $key);
               $key = str_replace('*', '', $key);
               if($key !="") //valid info
               {
                  $val = trim($row->find('td',1)->plaintext);
                  $minfo[$key]= $val;
               }
        }
        break;
        
        case 'minfor':
        $html = str_get_html($block);
        $trows = $html->find('tr'); // find all rows
        for($i=0; $i<count($trows); $i++)
        {
               $row = $trows[$i];
               $key = trim($row->find('td',0)->plaintext);
               $key = preg_replace("'\s+'", " ", $key);
               $key = str_replace('*', '', $key);
               if($key !="") //valid info
               {
                  $val = trim($row->find('td',1)->plaintext);
                  $minfo[$key]= $val;
               }
        }
        break;
        
        case 'binfo':
        $html = str_get_html($block);
        $trows = $html->find('tr'); // find all rows
        for($i=2; $i<count($trows); $i++)
        {
            $row = $trows[$i];
            //left 2 cols
            $key = trim($row->find('td',0)->plaintext);
            $key = preg_replace("'\s+'", " ", $key);
            $key = str_replace('*', '', $key);
            $val = trim($row->find('td',1)->plaintext);
            $val = str_replace(",","", $val);
            if($key !="") //valid info
            {
                $binfo [$key]= $val;
            }
            //right 2 cols
            $key = trim($row->find('td',2)->plaintext);
            $key = preg_replace("'\s+'", " ", $key);
            $key = str_replace('*', '', $key);
            $val = trim($row->find('td',3)->plaintext);
            $val = str_replace(",","", $val);
            if($key !="") //valid info
            {
                $binfo[$key]= $val;
            }    
        }
        break;
        
        case 'lagm':
            $html = str_get_html($block);
            $trows = $html->find('tr'); // find all rows
            for($i=0; $i<count($trows); $i++)
            {
                $row = $trows[$i];
                
                $key = trim($row->find('td',0)->plaintext);
                $key = preg_replace("/[\s]+/", " ", $key);
                $key = str_replace('*', '', $key);
                $key = trim($key);
                $val = trim($row->find('td',1)->plaintext);
                if($key !="") //valid info
                {
                    $lagm[$key] = $val;
                }
            }
        break;
        
        case 'intfin':
            //do a dirty hack to remove the inner table
            $bpos = strpos($block, '<table', 8);
            $epos = strpos($block, '</table', $bpos);
            $p1 =    substr($block, 0, $bpos);
            $p2 =    "Diluted EPS in BDT* (Based on continuing operations)";
            $p3 =    substr($block, $epos+8);
            $block = $p1 . $p2 . $p3;
            
            $bpos = strpos($block, '<table', $epos);
            $epos = strpos($block, '</table', $bpos);
            $p1 =    substr($block, 0, $bpos);
            $p2 =    "Diluted EPS in BDT* (Including Extra-ordinary Income)";
            $p3 =    substr($block, $epos+8);
            $block = $p1 . $p2 . $p3;
            
            $html = str_get_html($block);
             $trows = $html->find('tr'); // find all rows
            for($i=3; $i<count($trows); $i++)     // ignore the first 3 rows/header
            {
                $row = $trows[$i];
                
                $key = trim($row->find('td',0)->plaintext);
                $key = preg_replace("/[\s]+/", " ", $key);
                $key = str_replace('*', '', $key);
                $key = trim($key);
                $val1 = trim($row->find('td',1)->plaintext);
                $val2 = trim($row->find('td',2)->plaintext);
                $val3 = trim($row->find('td',3)->plaintext);
                $val4 = trim($row->find('td',4)->plaintext);
                
                $val = $val1.','.$val2.','.$val3.','.$val4;
                $val = preg_replace("/[\s\n]+/", " ", $val);
                $val = trim($val);
                
                
                if($key !="") //valid info
                {
                    $intfin[$key] = $val;
                }
            }
            break;
            
            case 'pstat':
                 $html = str_get_html($block);
                 $trows = $html->find('tr'); // find all rows        
                 foreach($trows as $row){
                    $key = trim($row->find('td',0)->plaintext);
                    $key = preg_replace("/[\s]+/", " ", $key);
                    $key = str_replace('*', '', $key);
                    $key = trim($key);
                    if($key != ""){
                        $val = trim($row->find('td',1)->plaintext) .','.trim($row->find('td',2)->plaintext);
                        $profstat[$key] = $val; 
                    }
                 }                         
            break;
            
            case  'cperat':
                // fix the missing <tr> tag
                $bpos = strpos($block, '<td');
                $block = substr($block, 0, $bpos-1) . '<tr>'.substr($block, $bpos);
                 $html = str_get_html($block);
                 $trows = $html->find('tr'); // find all rows        
                 foreach($trows as $row){
                    $key = trim($row->find('td',0)->plaintext);
                    $key = preg_replace("/[\s]+/", " ", $key);
                    $key = str_replace('*', '', $key);
                    $key = trim($key);
                    if($key != ""){
                        $val = trim($row->find('td',1)->plaintext);
                        $peratio[$key] = $val; 
                    }
                 }                         
            break;
            
            case 'fperf1':
                 $html = str_get_html($block);
                 $trows = $html->find('tr'); // find all rows
                 for($i=3; $i<count($trows); $i++)
                 {
                    $row = $trows[$i];
                
                    $key = trim($row->find('td',0)->plaintext);
                    $key = preg_replace("/[\s]+/", " ", $key);
                    $key = str_replace('*', '', $key);
                    $key = trim($key);
                    
                    if($key !="") //valid info
                    {   $vals = array();
                        $vals []= trim($row->find('td',1)->plaintext); // basic eps
                        $vals []= trim($row->find('td',3)->plaintext); // dil eps
                        $vals []= trim($row->find('td',5)->plaintext); // basic nav
                        $vals [] = trim($row->find('td',6)->plaintext); // dil nav
                        $vals [] = trim($row->find('td',7)->plaintext);
                        $val = implode(',', $vals);
                        $fperm1[$key] = $val;
                    }
                 }
            break;
            
             case 'fperf2':
                 $html = str_get_html($block);
                 $trows = $html->find('tr'); // find all rows
                 for($i=3; $i<count($trows); $i++)
                 {
                    $row = $trows[$i];
                
                    $key = trim($row->find('td',0)->plaintext);
                    $key = preg_replace("/[\s]+/", " ", $key);
                    $key = str_replace('*', '', $key);
                    $key = trim($key);
                    
                    if($key !="") //valid info
                    {
                        $val1 = trim($row->find('td',1)->plaintext);
                        $val2 = trim($row->find('td',3)->plaintext);
                        $val3 = trim($row->find('td',4)->plaintext);
                        $val = $val1 .','. $val2.','.$val3;
                        $fperm2[$key] = $val;
                    }
                 }
            break;
            
            case 'oinfo':
                $html = str_get_html($block);
                $trows = $html->find('tr'); // find all rows
                for($i=0; $i<count($trows); $i++)
                {
                    $row = $trows[$i];
                
                    $key = trim($row->find('td',0)->plaintext);
                    $key = preg_replace("/[\s]+/", " ", $key);
                    $key = str_replace('*', '', $key);
                    $key = trim($key);
                    if($key !="") //valid info
                    {
                       $val = trim($row->find('td',1)->plaintext); 
                       $oinfo[$key] = $val;
                    }
                 }
    }
   
}

function parse_holding_string($hstring)
{
    //parses the share holding string into pair values
    
    $holdings = array();
    
    $hstring = str_replace('vt.', 'vt ', $hstring);
    $hld = preg_split('/[\s]+/',$hstring);
    if(count($hld)==10){ // correct explodes
        $holdings['sd'] = $hld[1];
        $holdings['gv'] = $hld[3];
        $holdings['in'] = $hld[5];
        $holdings['fr'] = $hld[7];
        $holdings['pb'] = $hld[9];
    }    
 return $holdings;
}

function parse_dividend($dividend)
{
    $stock = 0;
    $cash  = 0;
    
    $pos = strpos($dividend, ',');
    if($pos !== false){
        //we have both stock and cash
        $cash = substr($dividend, 0, $pos-1);
        $stock = substr($dividend, $pos+1);
    }   
    else{ // either stock or cash
       $pos = strpos($dividend, '%B');
       if($pos !== false){
          $stock = $dividend;
          $stock = str_replace("%B", '', $stock);
       }
       else{
           $cash = $dividend;
       }
        
    }
    return array($stock, $cash);
}
?>
