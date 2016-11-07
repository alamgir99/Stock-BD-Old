<?php
// BRAC EPL data source parser

  //<table cellspacing="0" cellpadding="3"
//this function gets the price table from BRAC epl and fills the global var $snapshot_new
//change this if the provider is changed
function get_price_table()
{
    global $snapshot_new;
    global $firstsnap;
    global $topen, $yclose;
    
    $signature1 = "<table cellspacing=\"0\" cellpadding=\"3\"";  // price table begining signature
    $url1       = "http://www.bracepl.com/brokerage/Current-Stock-Price.php";
    
    $signature2 = "<TABLE cellSpacing=\"0\" cellPadding=\"2\""; // dsegen table
    $url2       = "http://www.bracepl.com/brokerage/Index-and-Graph.php";
    
    
    $fname = SBD_ROOT.'/prv/temp/brac_allprice.htm';
    $cmd = 'curl  '.$url1 .' --connect-timeout 25 --max-time 45 -o ' .$fname; 
    $err_code = exec ($cmd);
    
    if(filesize($fname)<400000) // less than 400k no good
    return;
    
    
    // read the entire file
    $fid = fopen($fname, "r");
    if($fid === false){
          log_msg(__FILE__, "Cant open file : ".$filename);
          exit(0);
    }
         
    $buffer = fread($fid, filesize($fname));
    fclose($fid);
    
    $bp = stripos($buffer, $signature1);
    $ep = stripos($buffer, "</table", $bp);
    $table = substr($buffer, $bp, $ep-$bp+8);
    
    $html = str_get_html($table);

    $rows = $html->find('tr');
    foreach($rows as $row){
        $tds = $row->find('td');
        if($tds[0]->plaintext == "#")
            continue;
            
       $ticker = trim($tds[1]->plaintext);
       $lt     = trim($tds[2]->plaintext);
       $hi     = trim($tds[3]->plaintext);
       $lo     = trim($tds[4]->plaintext);
       $vl     = trim($tds[9]->plaintext);    
       
       $snapshot_new[$ticker] = array(  
                                     "lt" => $lt, // ltp
                                     "hi" => $hi, //high
                                     "lo" => $lo, //low
                                     "vl" => $vl); // vol 
    }
    
    
    $fname = SBD_ROOT.'/prv/temp/brac_dsegen.htm';
    $cmd = 'curl  '.$url2 .' --connect-timeout 25 --max-time 45 -o ' .$fname; 
    $err_code = exec ($cmd);
    
    if(filesize($fname)<18000) // less than 200k no good
    return;
    
    
    // read the entire file
    $fid = fopen($fname, "r");
    if($fid === false){
          log_msg(__FILE__, "Cant open file : ".$filename);
          exit(0);
    }
    
    $buffer = fread($fid, filesize($fname));
    fclose($fid);
    
    
    $bp = stripos($buffer, $signature2);
    $ep = stripos($buffer, "</table", $bp);
    $table = substr($buffer, $bp, $ep-$bp+8);
  
    // find dsegen value  
    $html = str_get_html($table);  
    $rows = $html->find('tr');
    $tds = $rows[1]->find('td');
    $dsegen = trim($tds[1]->plaintext);
    $dsegen = str_replace(',', '', $dsegen);
    
    //find the vol and turnover
    $bp = stripos($buffer, "<table", $ep);
    $ep = stripos($buffer, "</table", $bp);
    $table = substr($buffer, $bp, $ep-$bp+8);
    
    $html = str_get_html($table);  
    $rows = $html->find('tr');
    $tds = $rows[1]->find('td');
    $dsevol = trim($tds[1]->plaintext);
    $dsevol = str_replace(',', '', $dsevol);
    
    $dseto = trim($tds[2]->plaintext);
    $dseto = str_replace(',', '', $dseto);
    
    
    $snapshot_new["00DSEGEN"] = array(  
                                     "lt" => $dsegen, // lt
                                     "hi" => $dsegen, //high
                                     "lo" => $dsegen, //low
                                     "vl" => $dsevol); // vol 
                                     
    $snapshot_new["00DSETO"] = array(  
                                     "lt" => $dsegen, //last trade
                                     "hi" => $dsegen, //high
                                     "lo" => $dsegen, //low
                                     "vl" => $dseto); // turnover
        
}      

?>
