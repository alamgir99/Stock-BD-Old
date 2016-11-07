<?php
  // banner creater
  // gets the last trade price
  // gets the yclose price
  // find  the diff and gainer/looser
  
  $dir = dirname(dirname(__FILE__)); // location of  root dir
  include $dir."/config.php";

   
  //global var
  $ltps = array();
  $ycls = array();
  $chng = array();
  $pch  = array();
  $gainer = 0;
  $looser = 0;
  $unchan = 0;
  
  /* get the LTP after reading the snapshot file */
  $fname = SBD_ROOT.'/prv/temp/snapshot.txt';
  $fid = fopen($fname, "r");
  if($fid===false){
      $time_sig = date("h:ia"); 
    echo '<div><div class="message"> Current time : '.$time_sig.'. Waiting for trading session to begin.<br /> No need to reload, will update automatically.</div></div';
    exit;
 }
    
  $buffer = fread($fid, filesize($fname));
  $buffer = explode("\n", $buffer);
  fclose($fid);
  
  foreach($buffer as $buf){
      $buf = explode(',',$buf);
      $ltps[$buf[0]] = $buf[4]; 
  }
  
  /* now get the YCOLSE from yclose file */
  
  $fname = SBD_ROOT.'/data/yclose.txt';
  $fid = fopen($fname,"r");
  if($fid===false)
    exit;
    
  $buffer = fread($fid, filesize($fname));
  $buffer = explode("\n", $buffer);
  fclose($fid);
  
  foreach($buffer as $buf){
      $buf = explode(',',$buf);
      $ycls[$buf[0]] = $buf[1]; 
  }
  
  //for each stock traded today
  foreach($ltps as $tick => $ltp){
       if(isset($ycls[$tick]) && $ltp != 0.00){
           $chng[$tick] = sprintf("%01.2f",$ltp-  $ycls[$tick]);
           $pch[$tick] =  sprintf("%01.2f", (($chng[$tick]*100)/$ycls[$tick]));
           
           if($chng[$tick] > 0){
               $gainer++;
           } else if($chng[$tick] < 0){
               $looser ++;
           } else {
               $unchan++;
           }
           
       }   
       $ltps[$tick] = sprintf("%01.2f", $ltp);
  }  
  
  $gainer_p = sprintf("%01.2f", $gainer*100/count($ltps));
  $looser_p = sprintf("%01.2f", $looser*100/count($ltps));
  $unchan_p = sprintf("%01.2f", $unchan*100/count($ltps));
  
  $tick_per_page = 11;
  $k = 0; // page counter
  $i = 1; // ticker counter
  $pages = array();
  foreach($chng as $tick => $ch){
	$pages[$k][$tick]= array($tick,  $ch);
	if($i%$tick_per_page == 0) { // time for a new page
		$k++;
	}
	$i++;
  }
  
  echo '<div>'; // outer div
  foreach($pages as $page)
  {
      echo '<div class="message">';
      echo '<table class="ticker"><tr>';
      foreach($page as $vals){
	 $ch = $vals[1];
          if($ch > 0){
              echo "<td  class=\"gnr\">".$vals[0]."</td>";
          } 
          elseif($ch < 0){
                echo  "<td class=\"lsr\">".$vals[0]."</td>";
          }
          else{
                echo "<td class=\"unc\">".$vals[0]."</td>";
          }
      }
      echo "</tr><tr>";  
      foreach($page as $vals){
          $tick = $vals[0];
	  $ch = $vals[1];
          if($ch > 0){
              echo "<td class=\"gnrp\">".$ltps[$tick] ."(".$pch[$tick]."%)</td>";
          } 
          elseif($ch < 0){
                echo "<td class=\"lsrp\">". $ltps[$tick]."(".$pch[$tick]."%)</td>";  
          }
          else{
                echo "<td class=\"uncp\">".$ltps[$tick]."(".$pch[$tick]."%)</td>"; 
          }
      }
      echo '</tr></table>';
      echo '</div>';
  }
  echo '</div>'; 
  
?>
