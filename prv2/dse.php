<?php
//gets price table from dse site
  function get_price_table()
{
    global $snapshot_new, $snapshot_old;
    global $firstsnap;
    global $topen, $yclose;
    global $time_sig;
    
    // might need to switch to curl based call
    //$fname = $bd.'/temp/dsedata3.htm';
    
    $fname = SBD_ROOT.'/prv/temp/dsesnap.htm';
    $cmd = 'curl http://dsebd.org/latest_share_price_all.php  --connect-timeout 25 --max-time 45 -o ' .$fname.' -e http://dsebd.org/latest_share_price_scroll_l.php'; 
    $err_code = exec ($cmd);
    
    if(filesize($fname)<150000) // less than 150k no good
    return;
    
    $html = file_get_html($fname);

    $rows = $html->find('tr');
    foreach($rows as $row)
    {
        $ticker = trim($row->childNodes(1)->plaintext); //ticker
        $ltp  = trim($row->childNodes(2)->plaintext); // LTP
        $high = trim($row->childNodes(3)->plaintext); // high
        $low  = trim($row->childNodes(4)->plaintext); //low
        $vol  = trim($row->childNodes(9)->plaintext); // vol
   //     $ycl  = trim($row->childNodes(6)->plaintext); // yesterday close
        
        if($ticker=="Trading Code" || $ticker =="") continue;
        if(preg_match("/\bT05Y/i", $ticker)) continue;
        if(preg_match("/\bT10Y/i", $ticker)) continue;
        if(preg_match("/\bT15Y/i", $ticker)) continue;
        if(preg_match("/\bT20Y/i", $ticker)) continue;
        if(preg_match("/\bT5Y/i", $ticker)) continue;
        if(preg_match("/\bDEB/i", $ticker)) continue;
        
        $op = $snapshot_old[$ticker]['op'];
        $snapshot_new[$ticker]=array("op" => $op, "hi" => $high, "lo"=>$low, "lt" => $ltp, "vl"=>$vol);
    }
    
    //LTP of first snap is assumed the open price (this is not accurate though) 
    if($firstsnap){
        foreach($snapshot_new as $ticker => $val) 
                   $snapshot_new[$ticker]['op']= $snapshot_new[$ticker]['lt'];
    }        
}      
?>
