<?php
if (!defined('PUN'))
    exit;
  
function message($message, $no_back_link = false)
{
if(defined('HEADER_INC'))
{ ; }
else{
    include SBD_ROOT."/pub/header.php";}
?>

<div id="main_full">   
    <h2><span>Information</span></h2>
    <p align="center"><?php echo $message ?></p>
    
    <?php if($no_back_link === false){ ?>
    <p align="center"><a href="javascript:history.go(-1);"> Go back </a> </p>
    <?php } ?>
</div>   
<?php

include SBD_ROOT."/pub/footer.php";

}
?>
