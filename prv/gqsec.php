<?php
  
//this function gets the price table from GQ and fills the global var $snapshot_new
//change this if the provider is changed
function get_price_table()
{
    global $snapshot_new;
    global $snapshot_count;
                                                   
    $fname = SBD_ROOT.'/prv/temp/gqs_allprice-'.sprintf("%03d",$snapshot_count).'.htm';
    $cmd = 'curl http://www.gqsecurities.org/latest_share_price.asp --connect-timeout 25 --max-time 45 -o ' .$fname; 
    
    // dont curl if is in debug mode, the file is already downloaded in temp folder
    if(!defined('__DEBUG__ON')) 
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
    unset($html);
    
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
    $fname = SBD_ROOT.'/prv/temp/gqs_dsegen-'.sprintf("%03d",$snapshot_count).'.htm';   
    $cmd = 'curl http://www.gqsecurities.org/com_all_inf.asp --connect-timeout 25 --max-time 45 -o ' .$fname; 
    
    // dont curl if is in debug mode, the file is already downloaded in temp folder
    if(!defined('__DEBUG__ON'))
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
    
    //date signature        
    $bp = strpos($text, "UPDATE :") + 8;
    $ep = strpos($text, ": TOTAL", $bp);
    $timesig = substr($text, $bp, $ep-$bp);
    
    //issues advanced
    $bp = strpos($text, "UP ")+3;  // start of the text UP
    $ep = strpos($text, ":", $bp); // end of the value, :
    $issue_ad = substr($text, $bp, $ep-$bp); // chuck out the number
    $issue_ad = trim($issue_ad);
    
    //issues declined
    $bp = strpos($text, "DOWN ")+5;  // start of the text DOWN
    $ep = strpos($text, ":", $bp); // end of the value, :
    $issue_dc = substr($text, $bp, $ep-$bp); // chuck out the number
    $issue_dc = trim($issue_dc);
    
    //issues unchanged
    $bp = strpos($text, "UCH ")+4;  // start of the text UCH
    $ep = strpos($text, "TOTAL", $bp); // end of the value, TOTAL
    $issue_uc = substr($text, $bp, $ep-$bp); // chuck out the number
    $issue_uc = trim($issue_uc);
    
    //turnover
    $bp = strpos($text, "TAKA (mn):", $bp)+10;
    $turnover = trim(substr($text, $bp));
    
    
    //dsegen value
    $html = str_get_html($row2);  
    $tds = $html->find('td');
    $dsegen = trim($tds[1]->plaintext);
      
    //adv, dec, unc volume
    $adv_vol = 0;
    $dec_vol = 0;  
    $unc_vol = 0;
    
    $sign = "<table border='0' cellspacing='0'";
    $bp = strpos($buffer, $sign);
    
    while($bp !== FALSE){
        $ep = strpos($buffer, "</table", $bp);
        $chunk = substr($buffer, $bp, $ep-$bp+8);
     
        $ticker = find_ticker($chunk,'99FF99'); // adv vol color is 99FF99
        if($ticker != ""){
                    $adv_vol +=  $snapshot_new[$ticker]['vl'];       
        } else{
            $ticker = find_ticker($chunk,'FF9966'); // dec vol color is FF9966
            if($ticker != ""){
                    $dec_vol +=  $snapshot_new[$ticker]['vl'];       
            }
            else {
                $ticker = find_ticker($chunk,'00CCFF'); // unc vol color is 00CCFF         
                if($ticker != ""){
                    $unc_vol +=  $snapshot_new[$ticker]['vl'];       
                }    
            }
        } // else  
        
        //find next block
        $bp = strpos($buffer, $sign, $ep);         
    } // while   
     
    // add the composite tickers       
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
                                     
   $snapshot_new["00ADV"]   = array(  
                                     "lt" => $issue_ad, // open
                                     "hi" => $issue_ad, //high
                                     "lo" => $issue_ad, //low
                                     "vl" => $adv_vol); // turnover                                   

   $snapshot_new["00DEC"]   = array(  
                                     "lt" => $issue_dc, // open
                                     "hi" => $issue_dc, //high
                                     "lo" => $issue_dc, //low
                                     "vl" => $dec_vol); // turnover                                   
                                     
   $snapshot_new["00UNC"]   = array(  
                                     "lt" => $issue_uc, // open
                                     "hi" => $issue_uc, //high
                                     "lo" => $issue_uc, //low
                                     "vl" => $unc_vol); // turnover                                   
}      

//for attribute $attr, find all tickrs in $block
function find_ticker($block, $attr)
{
    $ticker = "";
    $html = str_get_html($block);
    $tdattrib = 'td[bgcolor=#'.$attr.']';
    $tds = $html->find($tdattrib);
    foreach($tds as $td)
    {
        $ticker = trim($td->plaintext);
        $ep = strpos($ticker, "&nbsp;");
        $ticker = substr($ticker, 0, $ep);
        $ticker = trim($ticker);
    }
    
    return $ticker;
}

?>
