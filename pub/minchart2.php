<?php

$dir = dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";
require SBD_ROOT.'/pub/include/common.php';

define('MINCHART_PAGE', 1); // this define helps add appropriate header
include SBD_ROOT."/pub/header.php";

//date_default_timezone_set("Asia/Dhaka"); 

if($sb_config['o_member_only'] === true){   
//is the user logged in
if($cur_user["userid"]=="guest"){
	message('This is a member only feature. Please <a href="register.php">register</a>. <br/>If you are already registered please <a href="login.php?redirect_url=minchart.php">login</a>.');
	exit;
	}
}


if(isset($_GET['ticker'])){
	$ticker = $_GET['ticker'];
}
else
	$ticker =""; // what is the instument name

if(isset($_GET['tdate']))
	$tdate =$_GET['tdate']; // what is the trading date, empty for current day
else {
    $today = date("Y-m-d");
    $today = strtotime($today);
    $day = date('D'); // today's day anme
    if($day=='Fri'){
        $tdate = strtotime('- 1 day', $today);
    }
    else if($day=='Sat'){
        $tdate = strtotime('- 2 day', $today);
    }
    else{
        $tdate = $today;
	}
    //convert to string format
    $tdate = date('Y-m-d', $tdate);
}

$livedata = true;
$today = date("Y-m-d");
if(strcmp($today, $tdate))
{
	$livedata = false;
}


$timenow = date("G");
if($timenow >= $sb_tradingend ) // trading hour is over
	$livedata = false;

//debug only
//$livedata = true;

$nodata_found = false;
$outv = array();
$outp = array();
$outx = array();
$avgvol = 0;  // average volume
          
if($livedata === false){
//which file contains the data
    $td_yr = substr($tdate, 0, 4);
    $fname = SBD_ROOT.'/data/mindata/'. $td_yr.'/'.$tdate.'/'. $ticker.'.txt';
    //echo $fname;
    $fid = @fopen($fname, "r");
    
    if($fid===FALSE )
    {
        $nodata_found = true;
    }
    else{ //read the data
			$totvol = 0;
			$totprice = 0;
			$count = 0;
			
            while(!feof ($fid)){  
                $content = fgetcsv($fid);
				if($content[2]==0) //skip zero vol
					continue;
                //time format from string to number
                $content[0] = strtotime($content[0]);
                if($content[0] > $lastx) // we have newer data
                {
                    $outx [] = $content[0]; // time stamp
                    $outp [] = $content[1]; // LTP
                    $outv [] = $content[2]; // vol
					
					$count++;
					$totvol += $content[2];
					$totprice += $content[1];
                }
            }
            fclose($fid);  
			
			$avgvol = (int)($totvol/$count);
       }
} 


?>
	
	<script type="text/javascript">
    	$(function() {
		$( "#tdate" ).datepicker({
			dateFormat: 'yy-mm-dd',
			beforeShowDay: function (day){
				if(day.getDay()==5 || day.getDay() == 6)
					return [false];
				else
					return [true];
			},
			maxDate:'+0d'
			});
		$("#submit").button();
	});
	</script>

    
	<?php //load the chart object only when there is a ticker selected
    if ($ticker == "") 	{;} else { ?> 
		<script type="text/javascript"> 
		var chart; // global
		var lastx=0; //global time value, for whch the last chart point has been fetched
		var bull = 0; //bull %
		var bear = 0; // bear
		var ticker = <?php echo '\''.$ticker.'\''; ?>;
		var reload_time = 60;
		
		Highcharts.setOptions({
			global: {
				useUTC: false			}
		});

        $(document).ready(function() {
		//time offset
		// 360 is equiv to GMT+6 (BST)
		var d = new Date();
		var timeoffset = (d.getTimezoneOffset()+360)*60; // time offset in seconds
				
            chart = new Highcharts.Chart({

                chart: {

                    renderTo: 'container',
                    //defaultSeriesType: 'spline',
                    defaultSeriesType: 'line',
                    events: {
                    <?php if($livedata=== true) { ?>    load: requestData  <?php } ?>
                    },
                    margin:[60, 85, 70, 85],
                    zoomType: 'x',
                    borderWidth:2,
                    borderColor: "#4572A7",
                    backgroundColor: "#FFFFFF",
                    showAxes:true,
                    shadow:false
                },

                title: {

                    text: <?php echo '\''.$ticker.' ('.$tdate.')'.'\''; ?>

                },

                xAxis: {

                    <?php if($livedata===true) {?>
                    type: 'datetime',
                    tickPixelInterval: 50, 
                    tickInterval:1000,
                     maxZoom: 1,   
                     <?php } else {?>
                       categories: [
                            <?php echo implode(',',$outx); ?>
                           ],
                       tickInterval: 10, 
                     <?php } ?>
                    labels: {
                        formatter: function() {
                            var tickval = this.value + timeoffset; 
                           return Highcharts.dateFormat('%I:%M%P', tickval*1000);
                        },
                        enabled:true,
                        rotation:-90,
                        align:'right',
						//step : 10
                    },
                    gridLineWidth: 1,
					plotLines: [{
						color:'red',
						dashStyle: 'longdashdot',
						value:<?php echo $avgvol; ?>,
						zIndex: 3
					}]
                }, 

                yAxis:[
                    { // price
                        minPadding: 0.2,
                        maxPadding: 0.2,
                        lineWidth: 1,
                        labels: {
                            /*formatter: function() {
                                return this.value +'tk';
                            }, */
                            style: {
                            color: '#4572A7'
                            },
                            x:-15, y:0
                        },
                        
                        title: {
                            text: 'Price',
                            margin: 70,
                            style: {
                                color: '#4572A7'
                            }
                        }
                    },
                    { // first series -  volume
                        minPadding: 0.2,
                        maxPadding: 0.2,
                        lineWidth: 1,
                        labels: {
                            /*formatter: function() {
                                return this.value +' mm';
                            }, */
                            style: {
                                color: '#89A54E'
                            },
                            x:15, y:0
                        },
                        
                        title: {
                            text: 'Volume',
                            margin: 70,
                            style: {
                                color: '#89A54E'
                            }
                        },
                        opposite: true,
						plotBands: [{ // Light air
							from: <?php echo $avgvol ?>,
							to:   <?php echo (int)($avgvol+$avgvol*8/100) ?>,
							color: 'red',
							label: {
								text: <?php 
										if($avgvol<1000) {
											echo '\''.$avgvol.'\''; 
										}else {
											echo '\''.((int)($avgvol/1000)).'k\'';
										} 
										?>,
								style: {
									color: 'red'
								},
								x:40,
								align:'right'
							},
							zIndex:3
						}]		
                    }
                    ],

                credits:{
                    enabled:true,
                    href:"http://stock-bd.com",
                    text:"Stock-BD.com",
                    style: {
                            color: '#4572A7',
                            right: '15px',
                            bottom:'15px'
                        }
                },
                legend: {
                //align:"left",
                enabled:false
                },
                tooltip: {
                    formatter: function() {
                        var unit = {
                            'Price': 'tk',
                            'Volume': ''
                        }[this.series.name];
                            //time number in milli sec, while php gives in sec
                            // add time offset
                        return 'At '+ Highcharts.dateFormat('%I:%M%P', (this.x+timeoffset)*1000) + '<br />' + this.series.name+': '+ this.y+unit;
                    }
                },
                    
                series: [ // in reverse order
                    {
                        name: 'Volume',
                        type:'column',
                        color: '#89A54E, #895A4E', // change the bar colours here
                        yAxis: 1,
                        data: [<?php if($livedata===false) echo implode(',',$outv);?>]  //load the data with php based on live or nonlive
                    },
                    {
                        name: 'Price',
                        type:'spline',
                        color: '#4572A7',
                        data: [<?php if($livedata===false) echo implode(',',$outp);?>], //load the data with php based on live or nonlive
                        
                        states: {
                            hover: {
                                lineWidth: 1
                            }
                        }
                    }
                    ],
                plotOptions:{
                    column:{
                        lineWidth:1,
                        shadow:false,
                        borderWidth:0
                    },
                    spline:{
                        lineWidth:1,
                        shadow:false,
                        marker:{enabled:true, radius:2}
                        },
                    line:{
                        lineWidth:1,
                        shadow:false
                        },
                    point:{
                        shadow:false
                    }
                }
                
            });
        
        
        });
        
		<?php
		if($livedata==true){
		?>
		/**
		 * Request data from the server, add it to the graph and set a timeout to request again
		 */
		function requestData() {
			$.ajax({
				url: 'min_server.php', 
				success: function(data) {
					var shift = false; //series.data.length > 80; // shift if the series is longer than 20
					var parts;
					var msg = data.charAt(0);
					
					//alert(data);
					if(msg == 'E'){ //dont reload on error
						$("#respmsg").html(data); // no need to reload
						$("#respmsg").show();
						$('span.countdown').hide();
						$('#container').hide();
	
					}
					else if(msg == 'W') { // a warning
						//alert(data);
						$("#respmsg").html(data);
						$("#respmsg").show();
						$('span.countdown').show();
						//$('#container').hide();
						//try reloading
						setTimeout(requestData, reload_time*1000);	
					}
					else if(msg == 'F') { //trade hour is finished
						//alert(data);
						$("#respmsg").html(data);
						$("#respmsg").show();
						$('span.countdown').hide();
						//$('#container').hide();
						//dont reload
					}

					else { // okay
						$("#respmsg").html('');
						$("#respmsg").hide();
						$('span.countdown').show();
						$('#container').show();
						
						parts= data.split(';'); // split at ;
						// add the point
						//alert((points[0]));
						var $data = eval(parts[0]);
						var i;
						for(i=0; i<$data.length; i++)
						{
							//alert('Seris 0 :'+$data[i]);
							chart.series[0].addPoint(eval('['+$data[i]+']'), true, shift);
							
						}
						
						$data = eval(parts[1]);
						for(i=0; i<$data.length; i++)
						{
							//alert('Series 1 :'+$data[i]);
							chart.series[1].addPoint(eval('['+$data[i]+']'), true, shift);
							//chnage the bar colour here
							
						}
						
						//change the time
						lastx = $data[i-1][0];
						//alert(lastx);
												
						//get the bull/bear %
						$data = eval(parts[2]);
						bull  = $data[0];
						bear = $data[1]; 
						//write this to subtitle
						//chart.setTitle({subtitle:{text:'Bull:'+bull +'%, bear:'+bear+'%'}});
						//chart.redraw();				
					
						// call it again after one second
						setTimeout(requestData, reload_time*1000);	
					}	
				},
				cache: false,
				data:'ticker='+escape(ticker)+'&lastx='+lastx,
				error: function error(req, status, error){
					$("#respmsg").html('Ajax error, will retry automatically.');
					$("#respmsg").show();
					setTimeout(requestData, reload_time*1000);	
				}
			});
		}

		$(document).ready(
			function myTimer(){
			$('span.countdown').countdown({seconds:reload_time});
			window.setTimeout(myTimer, reload_time*1000); 
			}
		);
	
	    <?php } ?>
		</script> 
	<?php } ?>	
		

		<div id="main_full">    
			<h2>Minute chart</h2>
				<div>
				<form method="get" accept-charset="utf-8"  action="minchart2.php">
				Instrument:
				<?php 
				
	
				?>
				<select id="ticker" name="ticker">
					<?php
                    $optionlist = array();
                     //now collect the stocks
					$query = 'SELECT ticker from sym_list ORDER BY ticker ASC';
					$result = $db->query($query);
					if($result){
						$aticker = $db->fetch_row($result);
						while($aticker){
                            $optionlist []= $aticker[0];
							$aticker = $db->fetch_row($result);
						}                
                    }	
                      
                    //now insert all the symbols
                    foreach($optionlist as $option){
                        if($option === $ticker)
                                echo '<option value="'. $option .'" selected>'. $option .'</option>';
                            else
                                echo '<option value="'. $option .'">'. $option .'</option>';
                    }           
                    
					?>			
				</select> &nbsp;&nbsp; Date: <input type="text" id="tdate" name="tdate"  value="<?php echo $tdate; ?>" style="width:70px"/>&nbsp;&nbsp;
				<input type="submit" name="submit" id="submit" value="Plot" style="width:70px"/>&nbsp;&nbsp;	
				<?php if($livedata){?>Automatically updates in :<span class="countdown"></span> sec.  <?php } ?>
				<span id="respmsg" style="width:200px;text-align:center; color:red;margin-left:10px;">
                <?php if($nodata_found===true && $ticker !== "") echo 'No data found.';?>
                 </span>
				</form>
				</div>
				
				<div style="height:10px;"> </div>
				<div id="container" style="width: 980px; height: 400px; margin: 0 auto; text-align:center">
				<p style="text-align:center; padding-top:20px;">Select an instrument and click on the Plot button.</p>
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
		
