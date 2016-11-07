<?php
$dir = '/www/stockbd'; //dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";

require SBD_ROOT.'/pub/include/common.php';

//$_GET["sector"] = "Oil, IT, Finance"; //,Power,Pharma,Misc";
//$_GET["norm"] = "true";

//$_GET["what"] = 2;

if(isset($_GET['sector'])){
	$sectors = mysql_real_escape_string($_GET['sector']);
	$sectors = explode(",", $sectors); // split into an array
}
else { //sector names are not provided
	$sql = "SELECT s_id FROM sectors_showing";
	$s_ids = array();
	if($result = $db->query($sql)){
		while($data = $db->fetch_row($result)){
			 $s_ids [] = $data[0]; 	
		}
		$db->free_result($result);
	}
	//now get the string names for s_ids
	$sectors = array();
	foreach($s_ids as $s_id) {
		$sql = "SELECT sector_name FROM sectors WHERE s_id =".$s_id .";";
		if($result=$db->query($sql)){
			$data = $db->fetch_row($result);
			$sectors [] = $data[0];
			$db->free_result($result);
		}
	}
}

//normalised figures
if(isset($_GET['norm']))
	$norm    = mysql_real_escape_string($_GET['norm']);
if($norm == 'false')
	$norm = 0;
else
	$norm = 1;
	


//type of chart
if(isset($_GET['what']))
	$what     = (int)$_GET['what'];
else
	$what = 0;
	

//get sectoral data	
$sectordata = array();
      
$tdates = array();
$dataarray  = array();


if($what==0 || $what==1) // area/column chart
	$limit = 20;
 if($what == 2) { // donut  
    $limit=3;
    $norm = 1; 
 }
	

foreach($sectors as $sector){
	$dataarray  = array();
	$datearray = array();
	
	$sector = trim($sector);
	$sql = 'SELECT s_id FROM sectors WHERE sector_name=\''.$sector. '\';';
	
	$result = $db->query($sql);
	if($result)
	    $sector_id = $db->fetch_row($result);
	 else 
	 continue; 
	 
	 $sector_id =  $sector_id[0];
	 
	 

if($norm)
	$sql = "SELECT tdate, nturnover from sector_data WHERE s_id=" .$sector_id ." ORDER BY tdate DESC LIMIT ". $limit;
else
	$sql = "SELECT tdate, turnover from sector_data WHERE s_id=" .$sector_id ." ORDER BY tdate DESC LIMIT ". $limit;
	//echo $sql;
	
	$result = $db->query($sql);
	if($result){
		//echo implode(',', $data);
		while($data = $db->fetch_row($result)){
			 $datearray [] = '\''.substr($data[0], 5, 5) .'\''; 	
			$dataarray [] = (real)$data[1];
		}
		$db->free_result($result);
	}
	$sectordata[$sector] = array_reverse($dataarray);
	$tdates = array_reverse($datearray);
	
	//echo implode(',', $dataarray) . "\n";
}

if($norm){
    $sect_other = array();
    for($i=0; $i <count($tdates); $i++)
    {
        $capsum = 0;
        foreach ($sectordata as $sectname => $sector){
            $capsum += $sectordata[$sectname][$i];
        }
        $sect_other[$i]= 100 - $capsum;
    }
    //inset a dummy sector
    $sectordata["Not Showing"] = $sect_other;
   //$sectordata = array_reverse($sectordata, true);
    //array_unshift($sectordata, $sect_other);
}

//echo $sectordata;
//convert date  string to numeric time
//foreach($tdates as $tdate)
  //  $tdates = strtotime ($tdates);


//echo $what;
//include the config
//include the db things
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Chart</title>
<script type="text/javascript" src="js/jquery.js"></script> 
<script type="text/javascript" src="js/highcharts.js"></script> 
<script type="text/javascript" src="js/modules/exporting.js"></script>
<?php 
if(($what==0) || ($what==1)) 
{
//daily turn over of selected sector for last 10 /15days
?>
<script type="text/javascript">
            var chart;
            $(document).ready(function() {
                chart = new Highcharts.Chart({
                    chart: {
                             renderTo: 'container',
                             margin:[80, 20, 60, 70],
                             borderWidth:2,
                             borderColor: "#4572A7",
                             backgroundColor: "#FFFFFF",
                             showAxes:true,
                             defaultSeriesType: <?php if($what==0) echo '\''.'areaspline'.'\''; else echo '\''.'column'.'\''; ?>
                    },
                    title: {
                        text: 'Sector-wise daily turnover' <?php if($norm) echo '+\' (in %)\''; ?>
                    },
                    legend: {
                        layout: 'horizontal',
                        align: 'center',
                        verticalAlign: 'top',
                        x: 0,
                        y: 35,
                        borderWidth: 1,
                        backgroundColor: '#FFFFFF'
                    },
                    xAxis: {
                            labels: {
                               rotation:-35,
                               align:'right'
                            },
                            tickmarkPlacement: 'on',
                            categories: [
                            <?php echo implode(',',$tdates); ?>
                            ]
                            },
                    yAxis: {
                        title: {
                            text: 'Turnover '+<?php if($norm) echo '\'%\''; else echo '\'(mn Tk)\''; ?>
                        },
			    labels :{	
				x:0, y:0
			    },
			    lineWidth: 0
                    },
                    tooltip: {
                        formatter: function() {
                                //return ''+ this.series.name+ '<br/> '+
                                //this.x +': '+ this.y +' mn';
					return '' + this.x + ', ' + this.series.name +'<br/>'+
					        'Turnover :'+this.y + <?php if($norm) echo '\' %\''; else echo '\'mn\''?>
                        }
                    },
                    credits: {
                              enabled:true,
                              href:"http://stock-bd.com",
                              text:"Stock-BD.com",
                              style: {
                              color: '#4572A7',
                              right: '15px',
                              bottom:'15px'
                              }
                    },
                    plotOptions: {
                        areaspline: {
                            fillOpacity: 0.4,
                            stacking: 'normal'
                        },
			    column: {
                            stacking: 'normal'
                        }	
                    },
                    series: [
                    <?php
                    foreach ($sectordata as $sname => $sdata) { 
                        $sql = 'SELECT title FROM sectors WHERE sector_name=\''.$sname.'\'';
                        $res=$db->query($sql);
                        if($res){
                            $sector_title = $db->fetch_row($res);
                            $sector_title = $sector_title[0];
                            }
                    
                        if(isset($sector_title)===false)
                            $sector_title = $sname;
                            
                        for($d=0; $d<count($sdata); $d++)
                            $sdata[$d] = sprintf("%01.2f", $sdata[$d]);    
                        ?>
                    {
                        name: <?php echo '\''.$sector_title .'\'' ?>,
                        data: [<?php echo implode(',', $sdata); ?>]
                    }, 
                    <?php } ?>
                    ]
                });
                
                
            });
            
</script>
	
<?php } else if($what==2){
//donought daily
$colours = array('#4572A7','#AA4643', '#89A54E','#80699B','#3D96AE', '#DB843D','#022F63','#701310','#466308','#6D21C7','#056782','#0616E0','#00423C');
$i = 0;
foreach($sectordata as $sector => $data){  
 array_unshift($data, $colours[$i++]);
 $sectordata[$sector] = $data;  
}

?>
<script type="text/javascript">
            var chart;
            $(document).ready(function() {
                chart = new Highcharts.Chart({
                    chart: {
                             renderTo: 'container',
                             margin:[40, 0, 0, 0],
                             borderWidth:2,
                             borderColor: "#4572A7",
                             backgroundColor: "#FFFFFF"
                    },
                    title: {
                        text: 'Sector-wise daily turnover' <?php if($norm) echo '+\' (in %)\''; ?>
                    },
                    subtitle: {
                        text: 'Outer circle: '+<?php echo $tdates[2]; ?>+', middle circle: ' +<?php echo $tdates[1]; ?>+ ', inner circle:'+<?php echo $tdates[0]; ?>
                    },     
                    legend: {
                        enabled:false
                    },     
                     tooltip: {
                        formatter: function() {
                            return '<b>'+ this.series.name +'</b><br/>'+ 
                                this.point.name +': '+ this.y +' %';
                        }
                    },
                    credits: {
                              enabled:true,
                              href:"http://stock-bd.com",
                              text:"Stock-BD.com",
                              style: {
                              color: '#4572A7',
                              right: '15px',
                              bottom:'15px'
                              }
                    },
					
                    series: [
                    <?php
                    
			for ($i=0; $i < count($tdates)-1; $i++) { ?>
                {	
				type:'pie',
				name: <?php echo $tdates[$i]; ?>,
                innerSize: <?php $size = ($i+1)*20; echo '\''.$size.'\%\'' ?>,
				data: [
				<?php 
				foreach($sectordata as $sector => $data){
                    $sql = 'SELECT title FROM sectors WHERE sector_name="'.$sector.'"';
                    $res=$db->query($sql);
                    if($res){
                        $sector_title = $db->fetch_row($res);
                        $sector_title = $sector_title[0];
                    }
                    
                    if(isset($sector_title)===false)
                        $sector_title = $sector;
                    ?>
					{name: <?php echo '\''.$sector_title.'\''; ?>,y: <?php echo sprintf("%01.2f",$data[$i+1]);?>, color:<?php echo '\''.$data[0].'\''?> },
                <?php    
				}
				?>
				],
                 dataLabels: {enabled:false}
                }, 
                <?php } ?>
                { //outer most pie    
                type:'pie',
                name: <?php echo $tdates[$i]; ?>,
                innerSize: <?php $size = ($i+1)*20; echo '\''.$size.'\%\'' ?>,
                data: [
                <?php 
                foreach($sectordata as $sector => $data){
                    $sql = 'SELECT title FROM sectors WHERE sector_name="'.$sector.'"';
                    $res=$db->query($sql);
                    if($res){
                        $sector_title = $db->fetch_row($res);
                        $sector_title = $sector_title[0];
                    }
                    
                    if(isset($sector_title)===false)
                        $sector_title = $sector;
                    ?>
                    {name: <?php echo '\''.$sector_title.'\''; ?>,y: <?php echo sprintf("%01.2f",$data[$i+1]);?>, color:<?php echo '\''.$data[0].'\''?> },
                <?php    
                }
                ?>
                ]
                }
                ],
		        });                
            });
            
</script>
<?php
}
else if($what==3){
//weekly pie/donought
}
?>		

</head>
<body>
<div id="container" style="width: 740px; height: 400px; margin: 0 auto"></div>
</body>
</html>