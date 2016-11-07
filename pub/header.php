<?php
define('HEADER_INC', 1)

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>

<title>Underground</title>

<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
<meta name="description" content="Site Description Here" />
<meta name="keywords" content="keywords, here" />
<meta name="robots" content="index, follow, noarchive" />
<meta name="googlebot" content="noarchive" />

<link rel="stylesheet" type="text/css" media="screen" href="style/style.css" />
<link rel="stylesheet" type="text/css" media="screen" href="style/jquery-ui.css" />

<script type="text/javascript" src="js/jquery.js"></script> 
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script> 
<script type="text/javascript" src="js/jquery.countdown.min.js"></script> 
	
<script type="text/javascript" src="js/highcharts.js"></script> 
<script type="text/javascript" src="js/title_hack.js"></script> 
<script type="text/javascript" src="js/modules/exporting.js"></script> 


<script type="text/javascript" src="js/ajaxticker.js"></script>
					
</head>
<body>     
<!-- wrap starts here -->
<div id="wrap">
        <!-- header -->
        <div id="header">            
            <span id="slogan"> DSE: data and tools </span>
            
            <!-- tabs -->
            <ul>
                <li <?php if (defined('INDEX_PAGE')) echo 'id="current"';?>><a href="index.php"><span>home</span></a></li>
                <li <?php if (defined('MINCHART_PAGE')) echo 'id="current"';?>><a href="minchart.php"><span>minute chart</span></a></li>
                <li <?php if (defined('MONITOR_PAGE')) echo 'id="current"';?>><a href="stockmonitor.php"><span>monitor</span></a></li>
	     <li <?php if (defined('MARKET_INFO_PAGE')) echo 'id="current"';?>><a href="marketinfo.php"><span>market info</span></a></li>	
                <li <?php if (defined('COMP_INFO_PAGE')) echo 'id="current"';?>><a href="compinfo.php"><span>comp info</span></a></li>
                <li <?php if (defined('SECTOR_PAGE')) echo 'id="current"';?>><a href="sectortools.php"><span>sector tools</span></a></li>
                <li <?php if (defined('DOWNLOAD_PAGE')) echo 'id="current"';?>><a href="downloads.php"><span>downloads</span></a></li>
                <li <?php if (defined('CONTACT_PAGE')) echo 'id="current"';?>><a href="contact.php"><span>contact</span></a></li>
            </ul>
        </div>
        
        <div id="header-logo">     
	<?php
	//include_once "tickscroller.php";
	?>
        </div>
