<?php
/*
login.php
for admin duties

*/
$dir = dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";

require SBD_ROOT.'/pub/include/common.php';


 // Load the register.php language file
require SBD_ROOT.'/pub/include/register_lang.php';
 if (isset($_POST['login']))
{
	//contact information
	$userid  = trim($_POST['req_userid']);
	$pass     = trim($_POST['req_password']);
	$destination_url  = htmlspecialchars($_POST['redirect_url']);
    if($destination_url !="")
          $destination_url = "index.php";
	
    if(strpos($sb_config['o_base_url'], $destination_url)===false)
		$destination_url = $sb_config['o_base_url'].'/'.$destination_url;
    
	//check for identity 
	//set cookie
	$password_hash = md5($pass);
    
    //query the db
    $query = 'SELECT id FROM '.$db->prefix.'users WHERE user_id=\''.$db->escape($userid).'\' AND password=\''.$password_hash.'\'';
    $result = $db->query($query) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
    if ($db->num_rows($result)){ // logon okay 
       $expire = ($sb_config['o_save_pass'] == '1') ? time() + 31536000 : 0;
	   pun_setcookie($userid, $password_hash, $expire);

	  // echo 'User :' .$userid .'Password :' .$pass.'. Log in success.';
	  //echo '<br />' . $destination_url;
	
	   //redirect
       header("Location:".$destination_url);
     	    
       }
       else{
           message('Incorrect login');
           exit;
       }
}
else if($_GET['action']=='logout')
{
	//logout
	pun_setcookie("guest", md5(uniqid(rand(), true)), time() + 31536000);
    $destination_url  = htmlspecialchars($_GET['redirect_url']);
    if($destination_url=="")
        $destination_url = "index.php";
    if(strpos("http://", $destination_url)===false)
        $destination_url = $sb_config['o_base_url'].'/'.$destination_url;
        
        message('You are now logged out.');
	 //redirect
        //header("Location:".$destination_url);
        exit;
}
//show registration  status form
else{
 $destination_url  = htmlspecialchars($_GET['redirect_url']);
 include SBD_ROOT."/pub/header.php";
 ?>                                      

    <div id="main">    
    <h2>Login</h2>
	<form id="register" accept-charset="utf-8"  method="POST" action="login.php">
        <input type="hidden" name="login" value="1" />
		<input type="hidden" name="redirect_url" value="<?php echo $destination_url;?>" />
		<label>User id :</label> 
		<input type="text" name="req_userid" id="req_userid" size="25" maxlength="25" /> 
		<label>Password :</label> 
		<input type="password" name="req_password" id="req_password" size="25" maxlength="25" /> 
		<br />
	<p align="center"><input type="submit" name="login" id="login" value="Log in" /></p>
	</form>

    </div> <!-- main -->
<?php include SBD_ROOT.'/pub/footer.php'; 
 } 

// End the transaction
$db->end_transaction();

// Close the db connection (and free up any result data)
$db->close();

?>