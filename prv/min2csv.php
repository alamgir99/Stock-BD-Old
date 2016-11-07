<?php
$dir = dirname(dirname(__FILE__)); // location of  root dir
include_once $dir.'/config.php'; // config info

if($sb_market_open === false)
exit(0);

include_once $dir.'/bare.php'; // basic logging functions

//trading day
$tdate = date("Y-m-d");
$date_yr  = substr($tdate,0, 4);  // just the year part         

//path to the folder
$srcd = SBD_ROOT.'/data/mindata/'.$date_yr;  
  
//destination file
$fid = fopen($srcd.'/dse-intra-'.$tdate.'.csv',"w");

//combine the files in this folder
$fsrc = $srcd.'/'.$tdate;

//get list of files/tickers
$files = directory_list($fsrc, true, false);
foreach($files as $tfile){
    $tick = explode('.',$tfile);
    $tick = $tick[0];
    $fname = $fsrc.'/'.$tfile;
    $fid2 = fopen($fname, 'r');
    $buffer = fread($fid2, filesize($fname));
    fclose($fid2);
        
    $lines = preg_split("/[\n]+/", $buffer);
    //combine these per-min data to 5-min data
    $i = 0;
    while($i < count($lines)){
        $line0 = explode(',',$lines[$i+0]);
        $line1 = explode(',',$lines[$i+1]);
        $line2 = explode(',',$lines[$i+2]);
        $line3 = explode(',',$lines[$i+3]);
        $line4 = explode(',',$lines[$i+4]);
               
       //we deal with five lines of data
        $ttime = $line0[0]; // trading time 
        $open  = $line0[1]; //  open
        $close = $line4[1]; //  close
               
        // got to change this later
        $high  = max($line0[1],$line1[1], $line2[1], $line3[1], $line4[1]);
        $low   = max($line0[1],$line1[1], $line2[1], $line3[1], $line4[1]);
        //add all the volumes
        $vol   = $line0[2] + $line1[2] + $line2[2] + $line3[2] + $line4[2];
              
         $i = $i + 5;
           
        // SYMBOL, DATE, TIME, OPEN, HIGH, LOW, CLOSE, VOLUME
         $content = $tick.','.$tdate.','.$ttime.','.$open.','.$high.','.$low.','.$close.','.$vol;
	 if($vol == 0)  //skip zero vol
            continue;
         fwrite($fid, $content);
         fwrite($fid, "\n");
     }
  }
 fclose($fid);
 
 //zip the file
 $srcfile = $srcd.'/dse-intra-'.$tdate.'.csv';
 $dstfile = $srcd.'/dse-intra-'.$tdate.'.zip';
 $command = 'zip -j '.$dstfile.' '.$srcfile;
 exec($command);
 unlink($srcfile);
  
  /**
* directory_list
* return an array containing optionally all files, only directiories or only files at a file system path
* @author     cgray The Metamedia Corporation www.metamedia.us
*
* @param    $base_path         string    either absolute or relative path
* @param    $filter_dir        boolean    Filter directories from result (ignored except in last directory if $recursive is true)
* @param    $filter_files    boolean    Filter files from result
* @param    $exclude        string    Pipe delimited string of files to always ignore
* @param    $recursive        boolean    Descend directory to the bottom?
* @return    $result_list    array    Nested array or false
* @access public
* @license    GPL v3
*/
function directory_list($directory_base_path, $filter_dir = false, $filter_files = false, $exclude = ".|..|.DS_Store|.svn", $recursive = true){
    $directory_base_path = rtrim($directory_base_path, "/") . "/";

    if (!is_dir($directory_base_path)){
        error_log(__FUNCTION__ . "File at: $directory_base_path is not a directory.");
        return false;
    }

    $result_list = array();
    $exclude_array = explode("|", $exclude);

    if (!$folder_handle = opendir($directory_base_path)) {
        error_log(__FUNCTION__ . "Could not open directory at: $directory_base_path");
        return false;
    }else{
        while(false !== ($filename = readdir($folder_handle))) {
            if(!in_array($filename, $exclude_array)) {
                if(is_dir($directory_base_path . $filename . "/")) {
                    if($recursive && strcmp($filename, ".")!=0 && strcmp($filename, "..")!=0 ){ // prevent infinite recursion
                        error_log($directory_base_path . $filename . "/");
                        $result_list[$filename] = directory_list("$directory_base_path$filename/", $filter_dir, $filter_files, $exclude, $recursive);
                    }elseif(!$filter_dir){
                        $result_list[] = $filename;
                    }
                }elseif(!$filter_files){
                    $result_list[] = $filename;
                }
            }
        }
        closedir($folder_handle);
        return $result_list;
    }
}
