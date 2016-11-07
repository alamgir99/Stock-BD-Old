<?php
$dir = dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";

require SBD_ROOT.'/pub/include/common.php';

define('COMP_INFO_PAGE', 1); // this define helps add appropriate header
include SBD_ROOT."/pub/header.php";

if($sb_config['o_member_only'] === true){
    //is the user logged in
    if($cur_user["userid"]=="guest"){
    
	message('This is a member only feature. Please <a href="register.php">register</a>. <br/>If you are already registered please <a href="login.php?redirect_url=minchart.php">login</a>.');
	exit;
	}
}


if(isset($_GET['ticker']))
    $ticker = $_GET['ticker'];
else
    $ticker =""; // what is the instument name

if(isset($_GET['sector']))
    $sector =$_GET['sector']; // is a sector is chosen
else {
    $sector = "All Sect";
}

$ticker = $db->escape($ticker); 
$sector = $db->escape($sector); 
?>
    
    <script type="text/javascript" language="javascript">
    $(function() {
        //alert("fired");
        $( "select#sector" ).bind('change keyup', function (){
            //alert($(this).val+' fired');
            var selected = $("#sector option:selected");
            if(selected.val() != 0){
              //  alert("fired" + selected.text());
              var sector_name =  selected.val();
              var datastring = 'sector='+sector_name;
              $.ajax({
                  type:"GET",
                  url:"ajaxhelper/gettickers.php",
                  data:datastring,
                  cache:false,
                  success: function(retdata){
                      //retdata is comma separated ticker list
                      //alert('ajax success'+retdata);
                      var tickers = eval(retdata); // tickers is a string array
                      var  ticksel = $("select#ticker");
                      var allitems=''; 
                      for(i=0; i<tickers.length; i++){
                        allitems += '<option value="'+tickers[i]+'">'+tickers[i]+'</option>';
                      }
                     //alert(allitems);
                     $("select#ticker").html(allitems);
                  }
              });
            }
            });
        
    $("#submit").button();
    });
    
    </script>
        
    <div id="main_full">    
        <h2>Company Information</h2>
            <div>
            <form method="get" accept-charset="utf-8"  action="compinfo.php">
                Sector:
                <select id="sector" name="sector">
                    <option value="All Sect"<?php if($sector=="All Sect") echo "selected";?>>All Sect</option>
                    <?php
                    $sql = 'SELECT sector_name FROM sectors;';
                    $result = $db->query($sql);
                    if($result){
                        $asector = $db->fetch_row($result);
                        while($asector){
                            if($asector[0]===$sector)
                                echo '<option value="'. $asector[0] .'" selected>'. $asector[0] .'</option>';
                            else
                                echo '<option value="'. $asector[0] .'">'. $asector[0] .'</option>';
                           $asector = $db->fetch_row($result);
                        }
                    }    
                    ?>            
                </select> &nbsp;&nbsp; 
                Symbol:
                <select id="ticker" name="ticker">
                    <?php
                    if($sector=="All Sect") {// no sector is specified
                        $query = 'SELECT ticker from sym_list ORDER BY ticker ASC';
                        $result = $db->query($query);
                        if($result){
                                $aticker = $db->fetch_row($result);
                                while($aticker){                                
                                    if(substr($aticker[0], 0, 2) != "00") {
                                      if($aticker[0]===$ticker)
                                        echo '<option value="'. $aticker[0] .'" selected>'. $aticker[0] .'</option>';
                                      else
                                        echo '<option value="'. $aticker[0] .'">'. $aticker[0] .'</option>';
                                    }
                                   $aticker = $db->fetch_row($result);
                                }
                         }                              
                    }
                    else {// a sector is specified, so get the sector id, then the symbol list
                        $sql = 'SELECT s_id FROM sectors WHERE sector_name=\''.$sector.'\';';
                        if($res=$db->query($sql)){
                              $sector_id = $db->fetch_row($res);
                              $sector_id = (integer)$sector_id[0];
                              
                         
                            $query = 'SELECT ticker from sector_def WHERE s_id='.$sector_id.' ORDER BY ticker ASC';
                            $result = $db->query($query);
                         
                            if($result){
                                $aticker = $db->fetch_row($result);
                                while($aticker){
                                    
                                    if($aticker[0]===$ticker)
                                        echo '<option value="'. $aticker[0] .'" selected>'. $aticker[0] .'</option>';
                                    else
                                        echo '<option value="'. $aticker[0] .'">'. $aticker[0] .'</option>';
                             
                                $aticker = $db->fetch_row($result);
                                }
                            }                              
                        }
                    }
                    ?>            
                </select> &nbsp;&nbsp; 
                 
                <input type="submit" name="submit" id="submit" value="Sow" style="width:70px"/>&nbsp;&nbsp;    
                &nbsp;&nbsp;(Select a sector and symbol then click on the Show button.)
                </form>
                </div>
                
                <div style="height:10px;"> </div>
                <div id="compinfocontainer">
                   <?php
                     if($ticker != '')
                     {
                         //get the information of this symbol and show
                         
                         //get basic info
                         $basic_info_found = false;
                         $sql = 'SELECT name, auth_cap, paid_cap, segment, face_value, lot_size, list_year, category, comment';
                         $sql = $sql . ' FROM comp_info WHERE ticker="'.$ticker.'"';
                         $res = $db->query($sql);
                         if($res){
                             list($name, $acap, $pcap, $seg, $fv, $ls, $ly, $cat, $com1) = $db->fetch_row($res); 
                             $basic_info_found = true;
                         }
                         
                         // holding info
                         $sql = 'SELECT hyear, spondir, gov, inst, forgn, pub, comment FROM ';
                         $sql = $sql . 'holdings WHERE ticker="'.$ticker.'"';
                         $res = $db->query($sql);
                         $holdings = array();
                         if($res){
                             while($holding = $db->fetch_row($res)){
                                $holdings []= $holding;
                             }
                         }
                         else
                            $basic_info_found = false;
                         
                         //financial info  audited
                         
                         //$sql = 'SELECT '
                         
                         //financial info interim
                         
                         //dividend history
                         
                         //company news
                         
                         
                         //now show the info we have gathered
                         if($basic_info_found){
                         ?>
                         <h3> All about <?php echo $ticker;?></h3>
                         <div class="box">
                         <div class="inform">
                         <fieldset>
                         <legend>&nbsp;Basic Information&nbsp;</legend>
                          <table>
                            <tr><td class="right">Compnay name/sector:</td><td class="left"> <?php echo $name.' ('.$seg.')';?> </td> </tr>
                            <tr><td class="right">Capital (mn BDT):</td><td class="left"> Authorised <?php echo $acap;?>, Paid-up <?php echo $pcap;?></td></tr>
                            <tr><td class="right">Face value/Lot size:</td><td class="left"> <?php echo $fv.'/'.$ls; ?> </td></tr>                            
                            <tr><td class="right">Category: </td> <td class="left"> <?php echo $cat; ?></td></tr>
                            <tr><td class="right">Listed in:</td><td class="left"> <?php echo $ly;?> </td></tr>
                            <tr><td class="right">Share holding:</td>
                                <td class="left"> <?php 
                                if(count($holdings)==1) { // show inline
                                    list($hyear, $spondir, $gov, $inst, $forgn, $pub, $com2) = $holdings[0];
                                    echo 'Sponsor/dir '.$spondir .'%, Gov '.$gov.'%, Inst '.$inst.'%, Foreign '.$forgn.'%, Public '.$pub.'%';
                                    if(isset($com2)) echo '<br />'.$com2;
                                }
                                else { // show in a table
                                    echo '<table style="cell">';
                                    echo '<tr><th>Year</th><th>Sponsor/dir</th><th>Govt</th><th>Inst</th><th>Foreign</th><th>Public</th><th>Remark</th></tr>';
                                      foreach($holdings as $holding){
                                          echo '<tr><td>'.substr($holding[0],0, 4).'</td><td>'.$holding[1].'</td><td>'.$holding[2].'</td><td>'.$holding[3].'</td>';
                                          echo '<td>'.$holding[4].'</td><td>'.$holding[5].'</td><td>'.$holding[6].'</td></tr>';
                                      }
                                    echo '</table>';
                                }
                                ?> </td>
                            </tr>
                          </table>
                          </fieldset>
                          </div>
                          </div>
                         <?php
                         } // basic info
                     }
                   ?>
                
                </div>
                <p style="text-align:center; padding-top:10px;">Drag and select the chart to zoom in for finer details.</p>
                </div>
    
        </div> <!-- main -->
<?php include SBD_ROOT.'/pub/footer.php'; 


// End the transaction
$db->end_transaction();

// Close the db connection (and free up any result data)
$db->close();

?>
        
