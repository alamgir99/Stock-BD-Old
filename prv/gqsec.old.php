<?php
  
//this function gets the price table from GQ and fills the global var $snapshot_new
//change this if the provider is changed
function get_price_table()
{
    global $snapshot_new;
    global $firstsnap;
    global $topen, $yclose;
                                                   
    $fname = SBD_ROOT.'/prv/temp/gqs_allprice.htm';
    $cmd = 'curl http://www.gqsecurities.org/latest_share_price.asp --connect-timeout 25 --max-time 45 -o ' .$fname; 
    $err_code = exec ($cmd);
    
    if(filesize($fname)<300000) // less than 300k no good
    return;
    
    
    $html = file_get_html($fname);

    $ret = $html->find('table');
    $datatable = $ret[7];
    $datatext = $datatable->plaintext;
    $startat = strpos($datatext,'VOL DIFF')+8; 
    $datatext =  substr($datatext,$startat);

    //echo $datatext;
    $dataarray = preg_split("/[\t]+/", $datatext);
    unset($datatext);
    
    $totvol = 0;
    foreach($dataarray as $data){
            $data = str_replace(',', '', $data); // revome the thousand separator comma
            $vals = preg_split("/[\r\n\s]+/", $data);
            if($vals[0]=="") continue;
            
            $ticker = trim($vals[0]); //ticker     
            $snapshot_new[$ticker] = array(  
                                     "lt" => trim($vals[7]), //close/last trade
                                     "hi" => trim($vals[5]), //high
                                     "lo" => trim($vals[6]), //low
                                     "vl" => trim($vals[8])); // vol   
           $totvol += trim($vals[8]); // add up the volume                          
        }
        
     
    //grab the DSEGEN index and turnover
    $fname = SBD_ROOT.'/prv/temp/gqs_dsegen.htm';   
    $cmd = 'curl http://www.gqsecurities.org/com_all_inf.asp --connect-timeout 25 --max-time 45 -o ' .$fname; 
    $err_code = exec ($cmd);
    
    if(filesize($fname)<40000) // less than 40k no good, ideally 79K
    return;
    
    $fid = fopen($fname, "r");
    if($fid){
        $buffer = fread($fid, filesize($fname));
        fclose($fid);
    }
    
    $bp = strpos($buffer, "<table"); // outer table
    $bp = strpos($buffer, "<table", $bp+8); // inner table begins
    $bpr = strpos($buffer, "<tr", $bp); // begins row
    $epr = strpos($buffer, "</tr>", $bpr); // end row
    
    $row1 = substr($buffer, $bpr, $epr-$bpr+5); // the first row of info
    
    $bp = strpos($buffer, "<table", $bp+8); // outer table
    $bp = strpos($buffer, "<table", $bp+8); // inner table begins
    $bpr = strpos($buffer, "<tr", $bp); // begins row
    $epr = strpos($buffer, "</tr>", $bpr); // end row
    
    $row2 = substr($buffer, $bpr, $epr-$bpr+5); // the second row of info
    
    
    //process them
   
    $html = str_get_html($row1);  
    $tds = $html->find('td');
    $text = $tds[1]->plaintext; // deal with $text now
    $text = str_replace(array("\r","\n"), array("",""), $text);  
    $text = str_replace("&nbsp;", " ", $text); 
    
    $bp = strpos($text, "UPDATE :") + 8;
    $ep = strpos($text, ": TOTAL", $bp);
    $timesig = substr($text, $bp, $ep-$bp);
    
    $bp = strpos($text, "TAKA (mn):", $bp)+10;
    $turnover = trim(substr($text, $bp));
    
    //dsegen value
    $html = str_get_html($row2);  
    $tds = $html->find('td');
    $dsegen = trim($tds[1]->plaintext);
    
    
    $snapshot_new["00DSEGEN"] = array(  
                                     "lt" => $dsegen, //lt
                                     "hi" => $dsegen, //high
                                     "lo" => $dsegen, //low
                                     "vl" => $totvol); // vol 
                                     
    $snapshot_new["00DSETO"] = array(  
                                     "lt" => $dsegen, // open
                                     "hi" => $dsegen, //high
                                     "lo" => $dsegen, //low
                                     "vl" => $turnover); // turnover   
}      
?>
