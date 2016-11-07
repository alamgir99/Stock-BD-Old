<?php
/*
  test the OCR project
  
          

*/

include_once ("ocreng.php");

$ocr = new ocr_eng();

//set init params
$ocr->init(5, 8, 10); // 10 templates, each 8x5

$templates['0'] = array(
                        array(0,0,1,0,0),
                        array(0,1,0,1,0),
                        array(1,0,0,0,1),
                        array(1,0,0,0,1),
                        array(1,0,0,0,1),
                        array(1,0,0,0,1),
                        array(0,1,0,1,0),
                        array(0,0,1,0,0)
                        ); 

$templates['1'] = array(
                        array(0,0,1,0,0),
                        array(0,1,1,0,0),
                        array(1,0,1,0,0),
                        array(0,0,1,0,0),
                        array(0,0,1,0,0),
                        array(0,0,1,0,0),
                        array(0,0,1,0,0),
                        array(1,1,1,1,1)
                        ); 

$templates['2'] = array(
                        array(0,1,1,1,0),
                        array(1,0,0,0,1),
                        array(0,0,0,0,1),
                        array(0,0,0,1,0),
                        array(0,0,1,0,0),
                        array(0,1,0,0,0),
                        array(1,0,0,0,0),
                        array(1,1,1,1,1)
                        ); 

$templates['3'] = array(
                        array(0,1,1,1,0),
                        array(1,0,0,0,1),
                        array(0,0,0,0,1),
                        array(0,0,1,1,0),
                        array(0,0,0,0,1),
                        array(0,0,0,0,1),
                        array(1,0,0,0,1),
                        array(0,1,1,1,0)
                        ); 
$templates['4'] = array(
                        array(0,0,0,1,0),
                        array(0,0,1,1,0),
                        array(0,1,0,1,0),
                        array(1,0,0,1,0),
                        array(1,0,0,1,0),
                        array(1,1,1,1,1),
                        array(0,0,0,1,0),
                        array(0,0,0,1,0)
                        ); 
$templates['5'] = array(
                        array(1,1,1,1,1),
                        array(1,0,0,0,0),
                        array(1,1,1,1,0),
                        array(1,0,0,0,1),
                        array(0,0,0,0,1),
                        array(0,0,0,0,1),
                        array(1,0,0,0,1),
                        array(0,1,1,1,0)
                        ); 
$templates['6'] = array(
                        array(0,0,1,1,1),
                        array(0,1,0,0,0),
                        array(1,0,0,0,0),
                        array(1,1,1,1,0),
                        array(1,0,0,0,1),
                        array(1,0,0,0,1),
                        array(1,0,0,0,1),
                        array(0,1,1,1,0)
                        ); 
$templates['7'] = array(
                        array(1,1,1,1,1),
                        array(0,0,0,1,0),
                        array(0,0,0,1,0),
                        array(0,0,1,0,0),
                        array(0,0,1,0,0),
                        array(0,1,0,0,0),
                        array(0,1,0,0,0),
                        array(1,0,0,0,0)
                        ); 
$templates['8'] = array(
                        array(0,1,1,1,0),
                        array(1,0,0,0,1),
                        array(1,0,0,0,1),
                        array(0,1,1,1,0),
                        array(1,0,0,0,1),
                        array(1,0,0,0,1),
                        array(1,0,0,0,1),
                        array(0,1,1,1,0)
                        ); 
$templates['9'] = array(
                        array(0,1,1,1,0),
                        array(1,0,0,0,1),
                        array(1,0,0,0,1),
                        array(1,0,0,0,1),
                        array(0,1,1,1,1),
                        array(0,0,0,0,1),
                        array(0,0,0,1,0),
                        array(1,1,1,0,0)
                        ); 

                        
                        
//   train the ocr
foreach($templates as $key => $temp)
{
    $ocr->train($temp, $key);
}

// now test the system
foreach($templates as $key => $temp)
{
    $rt = $ocr->test($temp);
    echo "Original: ".$key.", found:".$rt."\n";
}

