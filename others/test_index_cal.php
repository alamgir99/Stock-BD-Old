<?php
// test index calculation

$oldshot = array();
$newshot = array();   
$stocklist = array();

if (($handle = fopen("indexedstocks.txt", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
           $stocklist []= $data[0];
    }
    fclose($handle);
}
 
 foreach($stocklist as $stock){
     $oldshot[$stock] = array(0,0);
     $newshot[$stock] = array(0,0);
 }

if (($handle = fopen("dse-2011-07-20.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
	        if(isset($oldshot[$data[0]]))
	            $oldshot[$data[0]] = array($data[5], $data[6]); // only LTP and Vol
    }
    fclose($handle);
}


if (($handle = fopen("dse-2011-07-20.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if(isset($newshot[$data[0]]))
			$newshot[$data[0]] = array($data[5], $data[6]); // only LTP and Vol
    }
    fclose($handle);
}


$dsegen_op = 6619.087; 

 //find opening mcap
 $mcap_op  = 0;
 foreach($oldshot as $tick => $stock){
       $mcap_op  += ($stock[0] * $stock[1])/1e6;
 }
         

 //find current mcap
 $mcap_nw  = array();
 $dgen_cn  = array();
 $dsegen_nw = 0;
 
 foreach($newshot as $tick => $stock){
       $mcap_nw[$tick]  = ($stock[0] * $stock[1])/1e6;
       $dgen_cn[$tick]  = $dsegen_op*$mcap_nw[$tick]/$mcap_op;
       $dsegen_nw +=  $dgen_cn[$tick];
 }
         
 echo "DSE GEN now :" .$dsegen_nw ."\n";
          
         

         