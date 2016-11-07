<?php
?>
<script type="text/javascript" language="javascript">

//var xmlfile="http://"+window.location.hostname+"tick_server.php";
var xmlfile="tick_server.php"; //path to ticker txt file on your server.

//ajax_ticker(xmlfile, divId, divClass, delay, optionalfadeornot)
new ajax_ticker(xmlfile, "tickerbox", "", [2500, 60000], "fade"); // 3.5 stay, loads in 60 sec.
</script>
	