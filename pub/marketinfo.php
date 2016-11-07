<?php

$dir = dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";

require SBD_ROOT.'/pub/include/common.php';

define('MARKET_INFO_PAGE', 1); // this define helps add appropriate header
include SBD_ROOT."/pub/header.php";

if($sb_config['o_member_only'] === true){
    //is the user logged in
    if($cur_user["userid"]=="guest"){
    
	message('This is a member only feature. Please <a href="register.php">register</a>. <br/>If you are already registered please <a href="login.php?redirect_url=minchart.php">login</a>.');
	exit;
	}
}

if(isset($_GET['tdate_from']))
	$tdate_from =$_GET['tdate_from']; // starting date
else
	$tdate_from = date("Y:m:d");

if(isset($_GET['tdate_to']))
	$tdate_to =$_GET['tdate_to']; // ending date
else
	$tdate_to = date("Y:m:d");

if(isset($_GET['data_type']))
	$data_type = $_GET['tdate_to']; // type of data

//debug only
$livedata = true;


?>

	<script type="text/javascript" src="js/jquery.js"></script> 
	<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script> 
	<!--
	<script type="text/javascript" src="js/highcharts.js"></script> 
	<script type="text/javascript" src="js/title_hack.js"></script> 
	<script type="text/javascript" src="js/modules/exporting.js"></script>
	-->		
	

	<div id="main_full">
		<!-- Main start -->
		<h2>Stock monitor</h2>
		<div style="height:10px;"> </div>
				
			<div style="height:50px;"> </div>
			<div id="container" style="width: 780px; height: 200px; margin: 0 auto; text-align:center; border-top:1px solid #ccc">
				<p style="text-align:left; padding-top:10px;">
				Monitor your stock.
				</p>
			</div>
	</div> <!-- main -->
<?php include SBD_ROOT.'/pub/footer.php'; 


// End the transaction
$db->end_transaction();

// Close the db connection (and free up any result data)
$db->close();

?>
		
