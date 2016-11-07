<?php
$dir = dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";

require SBD_ROOT.'/pub/include/common.php';

if($sb_config['o_member_only'] === true){   
//is the user logged in
if($cur_user["userid"]=="guest"){
	message('This is a member only feature. Please <a href="register.php">register</a>. <br/>If you are already registered please <a href="login.php?redirect_url=minchart.php">login</a>.');
	exit;
	}
}

$today = date('Y-m-d'); // today's date
$today = strtotime($today);
$day = date('D'); // today's day anme
if($day=='Fri'){
    $today = strtotime('- 1 day', $today);
}
else if($day=='Sat'){
    $today = strtotime('- 2 day', $today);
}
 else{
        $tdate = $today;
    }
    
$tdate_from = date("Y-m-d", $today);// starting date
$tdate_to = date("Y-m-d", $today); //ending date


$data_type = 'csv'; // default type of data
   
//debug only
$livedata = true;


define('DOWNLOAD_PAGE', 1); // this define helps add appropriate header
include SBD_ROOT."/pub/header.php";

?>

	<script>
	$(function() {
		$( "#date_from" ).datepicker({
			dateFormat: 'yy-mm-dd',
			beforeShowDay: function (day){
				if(day.getDay()==5 || day.getDay() == 6)
					return [false];
				else
					return [true];
			}
			});
		$( "#date_to" ).datepicker({
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

	<div id="main_full">
		<!-- Main start -->
		<h2>Data downloads</h2>
		<div style="height:10px;"> </div>
       	<h3>Select dates and data type</h3>
			<div style="margin:5px; height:20px; padding:5px;">
				<form method="get" accept-charset="utf-8"  action="dodownload.php">
                From: <input type="text" id="date_from" name="date_from"  value="<?php echo $tdate_from; ?>" style="width:70px"/>&nbsp;&nbsp;
				To: <input type="text" id="date_to" name="date_to"  value="<?php echo $tdate_from; ?>" style="width:70px"/>&nbsp;&nbsp;	                Type: 
				<select id="data_type" name="data_type">
				<option value="csv" selected> End of day CSV </option>
				<option value="mst" > End of day MST </option>
				<option value="min" > Minute data CSV</option>
				</select>
				<input type="submit" name="submit" id="submit" value="Get Data" style="width:70px"/>
				</form>
			</div>
				
				
			<div style="height:50px;"> </div>
			<div id="container" style="width: 780px; height: 300px; margin: 0 auto; text-align:center; border-top:1px solid #ccc">
				<p style="text-align:left; padding-top:10px;">
				<ol style="text-align:left">
                <h4>Notes on data </h4>
                <li>  If a date range (multiple days) is selected, data files are<b> always zipped.</b> Data during trade hour are not accurate, and do not have sector and index information. 
                 <b>Day-end data are available after 7.30pm BST</b> (when DSE makes it available with certainty.)</li><br />
				<li>End of day CSV data are formatted as: ticker, date, open, high, low, close, volume. For an instrument the odd lot and bulk trade volume
                 are also added if the instrument is traded in the main trading floor.</li><br />
				<li>
				MST files are raw files collected frm DSE site on each trading day. They do not have any rigorous format and are stored here for archive purpose only.
				</li><br />
				<li>
				Intra-day (minute) data are CSV formatted as: time, last trade price and volume. File name is the ticker name. Data for each trading day are served in a zip file containing one file for each ticker. If you select multiple trading days, all the data files will be zipped to a single file for convenience with trading dates as folder.</li>
				</ul>
				</p>
			</div>       
	</div> <!-- main -->
<?php 
include SBD_ROOT.'/pub/footer.php'; 


// End the transaction
$db->end_transaction();

// Close the db connection (and free up any result data)
$db->close();
?>
		
