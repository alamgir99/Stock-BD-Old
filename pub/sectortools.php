<?php
$dir = dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";

require SBD_ROOT.'/pub/include/common.php';

define('SECTOR_PAGE', 1); // this define helps add appropriate header
include SBD_ROOT."/pub/header.php";
 
 if($sb_config['o_member_only'] === true){   
    //is the user logged in
    if($cur_user["userid"]=="guest"){
	    message('This is a member only feature. Please <a href="register.php">register</a>. <br/>If you are already registered please <a href="login.php?redirect_url=minchart.php">login</a>.');
	    exit;
	}
 }
?>
<script type="text/javascript" src="js/jquery.js"></script> 
<script type="text/javascript" src="js/jquery-ui-1.8.6.custom.min.js"></script> 
<script type="text/javascript" src="js/jquery.countdown.min.js"></script> 
	
<script type="text/javascript" src="js/highcharts.js"></script> 
<script type="text/javascript" src="js/title_hack.js"></script> 
<script type="text/javascript" src="js/modules/exporting.js"></script>
	
<script language="Javascript" type="text/javascript">

function AddSector()
{
	//alert('add');
	seclist = document.getElementById("seclist");
	secid = seclist.selectedIndex;
	if(secid == -1){
		alert('Select a sector name from the drop list.');
		return;
	}
	
	secshowlist = document.getElementById("secshowlist");
	no_sec = secshowlist.length;

	//check if already added
	if(no_sec >=10){
		alert('Too many sectors, max 10 allowed.');
		return;
	}

	for(i=0; i<secshowlist.length; i++)
	{
		if(secshowlist.options[i].text==seclist.item(secid).text)
		{
			alert('\"'+seclist.item(secid).text +'\"'+' is alreay in the shown list.'); 
			return;
		}
	}
	
	var y=document.createElement('option');
	y.text = seclist.options[seclist.selectedIndex].text;
    y.value = seclist.options[seclist.selectedIndex].value;
	
	try	{
		secshowlist.add(y,null); // standards compliant
	}	
	catch(ex)	{
		secshowlist.add(y); // IE only
	}

return true;
}

function RemoveSector()
{
  //alert('Remove');
   var secshowlist = document.getElementById("secshowlist");
   
  // alert(secshowlist.selectedIndex);
   if(secshowlist.selectedIndex > -1) 
	secshowlist.remove(secshowlist.selectedIndex);
   else 
	alert('Select a sector from left.');
return true;
}


function ShowSector($seltab)
{
//form a string with all the sected sectors
   var secshowlist = document.getElementById("secshowlist");
   var sectors = "";
   for(i=0; i<secshowlist.length-1; i++)
	{
	 sectors = sectors + secshowlist.options[i].value + ',';
	}
	
	//the last item
	sectors = sectors + secshowlist.options[i].value;
//	alert(sectors);
	
	var normchk = document.getElementById("btnnorm");
	var normtick = normchk.checked;

	//get the chart and show	
	var $alltabs = $('#contenttabs').tabs();
	var $selected;
	if($seltab==-1)
		$selected = $alltabs.tabs('option', 'selected'); // => 0
	else
		$selected = $seltab;
		
	//var url = 'ajaxhelper/test.php?sector='+sectors+'&norm='+normtick+'&what='+$selected;
	var url = 'getchart.php?sector='+sectors+'&norm='+normtick+'&what='+$selected;
	
	url = encodeURI(url);
	//alert(url);
//	alert(selected);

	var tabs = ["darea","dbar","dpie","wpie"];
	document.getElementById(tabs[$selected]).src=url;	
}

function ShowTickers()
{
//show the tikcers belonging to a sector
   var secshowlist = document.getElementById("secshowlist");
   
   if(secshowlist.selectedIndex <= -1)  // no item selecet
   {   alert('Select a sector from the left.');
	return;
   }
   
   var sector = secshowlist.options[secshowlist.selectedIndex].value;
   var url = 'ajaxhelper/gettickers.php?sector='+sector;
  //alert(url);   
  $( "#contenttabs").tabs( "select" , 4); 
   $( "#others" ).load(encodeURI(url));
   sleep(2000);
}

$(function() {
	$( "#contenttabs" ).tabs({
		ajaxOptions: {
		error: function( xhr, status, index, anchor ) {
		$( anchor.hash ).html(
			"Couldn't load this tab. We'll try to fix this as soon as possible. " +
			"If this wouldn't be a demo." );
		}
		}
	});
  });
 
  $(document).ready(function() {
    $("#btnaddsector").button();
    $("#btnremovesector").button();
    $("#btnshowtickers").button();
     $("#btndsesectors").button();
    $("#btnshowsector").button();
    ShowSector(0);   
    });
   
</script>
<div id="main_full">
<div style="width:240px; float:left;">
<form name="frmMain" id="frmMain">
<div style="border:1px solid #ccc; padding:2px;">
<b>Sectors:</b> <br/>
<select  id="seclist" size="1" style="width:135px;text-align:left; float:left;">
<?php
	$query = 'SELECT sector_name, title from sectors WHERE s_id > 0 ORDER BY sector_name ASC';
	$result = $db->query($query);
	if($result){
		$sector = $db->fetch_row($result);
		while($sector){
		echo '<option value=' . $sector[0] .'>' . $sector[1] . '</option>';
		$sector = $db->fetch_row($result);
		}
		$db->free_result($result);
	}
?>
</select>
<input type="button"  id="btnaddsector" onclick="AddSector();" style="float:right;width:50px" value="Add" title="Add a sector to the list below"/>
<div style="height:30px;">  </div>
<b>Now Showing:</b><br/>
<select  id="secshowlist" size="10" style="width:110px;float:left;">
<?php
		$query = 'SELECT sectors.sector_name, sectors.title from sectors_showing, sectors WHERE sectors_showing.s_id=sectors.s_id';
		$result = $db->query($query);
		if($result){
			$sector = $db->fetch_row($result);
			while($sector){
			echo '<option value=' . $sector[0] .'>' . $sector[1] . '</option>';
			$sector = $db->fetch_row($result);
			}
			$db->free_result($result);
		}
?>
</select>
<input type="button"  id="btnremovesector" onclick="RemoveSector();"  style="width:75px;float:right;" value="Remove" title="Remove the selected sector from the list"/>
<input type="button" id="btnshowtickers" onclick="ShowTickers();"  style="width:75px;float:right;margin-top:5px;" value="Show Inst" title="Show the instruments of selected sector"/>

<div style="height:10px; clear:left;"></div>

&nbsp;<input type="checkbox"  id="btnnorm" <?php if((string)$_GET['norm']=='true') echo "checked"; ?> /> &nbsp;Normalise (wrt total TO)

<div style="height:20px; clear:left;"></div>

<input type="button"  id="btnshowsector" onclick="ShowSector(-1);" style="margin-left:30px;" value="Show Graph" />

</div>
</form>
</div>

<div style="float:right; width:755px;margin-left:-5px">
<div id="contenttabs">
	<ul>
		<li><a href="#darea" onclick="ShowSector(0); ">Daily Area</a></li>
		<li><a href="#dbar" onclick="ShowSector(1); ">Daily Bar</a></li>
		<li><a href="#dpie" onclick="ShowSector(2); ">Daily Pie</a></li>
		<li><a href="#wpie" onclick="ShowSector(3);">Weekly Pie</a></li>
		<li><a href="#others">Other</a></li>
	</ul>
	<iframe id="darea"  src="" scrolling="no" frameborder="0" style="height:410px; width:750px; margin-left:-20px;margin-top:-15px;">
	</iframe>
	
	<iframe id="dbar" src="" scrolling="no" frameborder="0" style="height:410px; width:750px; margin-left:-20px;margin-top:-15px;">
	</iframe>
	<iframe id="dpie" src="" scrolling="no" frameborder="0" style="height:410px; width:750px; margin-left:-20px;margin-top:-15px;">
	</iframe>
	<iframe id="wpie" src="" scrolling="no" frameborder="0" style="height:410px; width:750px; margin-left:-20px;margin-top:-15px;">
	</iframe>
	<div id="others">
	</div>
</div> <!-- contenttabs-->

<div> any note or observation </div>
</div>

</div> <!-- main -->
<?php include SBD_ROOT.'/pub/footer.php'; 
 
// End the transaction
$db->end_transaction();
// Close the db connection (and free up any result data)
$db->close();

?>

