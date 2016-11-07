<?php
//this function gets the price table from DSE and fills the global var $snapshot_new
//change this if the provider is changed
  function get_price_table()
{
    global $snapshot_new;
	global $snapshot_count;
	global $dseurl;
   
    $fname = SBD_ROOT.'/prv/temp/dse_allprice-'.sprintf("%03d",$snapshot_count).'.htm';
    $cmd = 'curl '.$dseurl.'/latest_share_price_all.php  --connect-timeout 25 --max-time 45 -o ' .$fname.' -e '.$dseurl.'/latest_share_price_scroll_l.php'; 
        // dont curl if is in debug mode, the file is already downloaded in temp folder
    if(!defined('__DEBUG__ON')) 
        $err_code = exec ($cmd);
		
	if(filesize($fname)<150000) // less than 150k no good
		return;
    
    $html = file_get_html($fname);

    $rows = $html->find('tr');
    foreach($rows as $row)
    {
        $ticker = trim($row->childNodes(1)->plaintext); //ticker
        $ticker = trim(str_replace("&nbsp;","",$ticker));
        if($ticker=="Trading Code" || $ticker =="") continue;
        
        
   //     $ycl  = trim($row->childNodes(6)->plaintext); // yesterday close
        
        if(preg_match("/\bT05Y/i", $ticker)) continue;
        if(preg_match("/\bT10Y/i", $ticker)) continue;
        if(preg_match("/\bT15Y/i", $ticker)) continue;
        if(preg_match("/\bT20Y/i", $ticker)) continue;
        if(preg_match("/\bT5Y/i", $ticker)) continue;
        if(preg_match("/\bDEB/i", $ticker)) continue;
        
        $ltp  = trim($row->childNodes(2)->plaintext); // LTP
        $high = trim($row->childNodes(3)->plaintext); // high
        $low  = trim($row->childNodes(4)->plaintext); //low
        $vol  = trim($row->childNodes(9)->plaintext); // vol
        
        //$op = $snapshot_old[$ticker]['op'];
        $snapshot_new[$ticker]=array("lt" => $ltp, "hi" => $high, "lo"=>$low, "vl"=>$vol);
    }

    //grab the DSEGEN index and turnover
    $fname = SBD_ROOT.'/prv/temp/dse_dsegen-'.sprintf("%03d",$snapshot_count).'.htm';   
    $cmd = 'curl '.$dseurl.'  --connect-timeout 25 --max-time 45 -o ' .$fname; 
    
    // dont curl if is in debug mode, the file is already downloaded in temp folder
    if(!defined('__DEBUG__ON'))
        $err_code = exec ($cmd);
    
    if(filesize($fname)<120000) // less than 120k no good, ideally 150K
    return;
	
	$fid = fopen($fname, "r");
	if($fid){
		$buffer = fread($fid, filesize($fname));
		fclose($fid);
	}	
	
	$bp = strpos($buffer, "<TABLE cellPadding=2 width=424"); // begining of the index table
	$bp = strpos($buffer, "<TABLE", $bp+20); // inner table
	$ep = strpos($buffer, "</TABLE>", $bp); // end of table
	
	$table1 = substr($buffer, $bp, $ep-$bp+8); // get the index table
	
	$bp = strpos($buffer, "<TABLE", $ep-8); // beginning of gainer/looser table
	$ep = strpos($buffer, "</TABLE", $bp);
	$table2 = substr($buffer, $bp, $ep-$bp+8); // get the TO table
	
	//gainer/looser table
	$bp = strpos($buffer, "<TABLE", $ep-8);
	$ep = strpos($buffer, "</TABLE", $bp);
	$table3 = substr($buffer, $bp, $ep-$bp+8); // get the gainer/looser
	
	//free the memory
    unset($buffer);
    
    //dsegen index
    $html = str_get_html($table1);
    $rows = $html->find('tr');
    
    // 2nd and 3rd td of 2nd row
    $row2 = $rows[1];
    $tds = $row2->find('td');
    $dsegen = trim($tds[1]->innertext);
    $dgen_ch  = trim($tds[2]->innertext);
    
    
    //get Vol and TO
    $html = str_get_html($table2);
    $rows = $html->find('tr');
    
    // 2nd and 3rd td of 2nd row
    $row2 = $rows[1];
    $tds = $row2->find('td');
    $dgen_vol = trim($tds[1]->innertext);
    $dgen_to  = trim($tds[2]->innertext);
	
    // add the composite tickers       
    $snapshot_new["00DSEGEN"] = array(  
                                     "lt" => $dsegen, //lt
                                     "hi" => $dsegen, //high
                                     "lo" => $dsegen, //low
                                     "vl" => $dgen_vol); // vol 
                                     
    $snapshot_new["00DSETO"] = array(  
                                     "lt" => $dsegen, // open
                                     "hi" => $dsegen, //high
                                     "lo" => $dsegen, //low
                                     "vl" => $dgen_to); // turnover     
}      
?>
