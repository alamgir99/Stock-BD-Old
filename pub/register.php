<?php
/***********************************************************************

  Copyright (C) 2002-2008  PunBB

  This file is part of PunBB.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/
$dir = dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";

require SBD_ROOT.'/pub/include/common.php';

define('PUN_SIGNUP_PAGE', '1');

 // Load the register.php language file
require SBD_ROOT.'/pub/include/register_lang.php';
require SBD_ROOT.'/pub/include/captcha.php';


if ($sb_config['o_regs_allow'] == '0'){
	message($lang_register['No new regs']);
    exit;
}

// User pressed the cancel button
if (isset($_GET['cancel'])){
	redirect('index.php', $lang_register['Reg cancel redirect']);
    exit;
}

//load the header
require SBD_ROOT.'/pub/header.php';  

//show user agree things
if (!isset($_GET['agree']) && !isset($_POST['form_sent']))
{        
?>
<div class="main-full">
	<h2><span><?php echo $lang_register['Site Rules'] ?></span></h2>
	<div class="box">
		<form method="get" accept-charset="utf-8"  action="register.php">
			<div class="inform">
				<fieldset>
					<legend>&nbsp;<?php echo $lang_register['Rules legend'] ?>&nbsp;</legend>
					<div class="infldset">
						<p><?php echo $lang_register['Rules Message'] ?></p>
					</div>
				</fieldset>
			</div>
			<p align="center"><input type="submit" name="agree" value="<?php echo $lang_register['Agree'] ?>" />&nbsp;&nbsp;<input type="submit" name="cancel" value="<?php echo $lang_register['Cancel'] ?>" /></p>
		</form>
	</div>
</div>

<?php
include SBD_ROOT."/pub/footer.php";
}
// do registration
else if (isset($_POST['form_sent']))
{
	// Check that someone from this IP didn't register a user within the last hour (DoS prevention)
   // $query = 'SELECT 1 FROM '.$db->prefix.'users WHERE registration_ip=\''.get_remote_address().'\' AND registration_time > '.(time() - 3600);
//	$result = $db->query($query) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());

	//if ($db->num_rows($result))
	//	message($lang_register['Recent Registered']);

	//basic information- must
	$name = trim($_POST['req_name']);
	$email  = strtolower(trim($_POST['req_email']));
	$user_id = trim($_POST['req_user_id']);
	$password  = trim($_POST['req_password']);
	
	//contact information
    if(isset($_POST['req_mobile']))
	    $mobile = trim($_POST['req_mobile']);
    else
        $mobile = '';
        
	$address  = trim($_POST['req_address']);
	
	
	$captcha_ques = trim($_POST['req_captcha_ques']);
	$captcha_ans        = trim($_POST['req_captcha_ans']);
	
    //first check the captcha
    $res = strcmp($captcha_correct_ans[$captcha_ques], $captcha_ans);
    if( $res != 0){
        message($lang_register['Captcha wrong answer.']);
        exit;
    }
    
	$status = "ok";
	
	//check if already registered, then quit
	// Validate e-mail
	require SBD_ROOT.'/pub/include/email.php';

	if (!is_valid_email($email)) {
		message($lang_register['Invalid e-mail']);
        exit;
    }


	// Check if someone else already has registered with that e-mail address
	$dupe_list = array();
    if($mobile == "")
        $query = 'SELECT id FROM '.$db->prefix.'users WHERE email=\''.$email.'\'';
    else
        $query = 'SELECT id FROM '.$db->prefix.'users WHERE email=\''.$email.'\' OR mobile=\''.$mobileno.'\'';
	
    $result = $db->query($query) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	if ($db->num_rows($result))
	{
		message($lang_register['Dupe e-mail']);
        exit;
	}

	//this will ensure overwriting registered members
	//-------------------- error checking
	// Convert multiple whitespace characters into one (to prevent people from registering with indistinguishable usernames)
	$name = preg_replace('#\s+#s', ' ', $name);

	// check Name
	if ((strlen($name) < 5) || (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $name)) ||
		((strpos($name, '[') !== false || strpos($name, ']') !== false) && strpos($name, '\'') !== false && strpos($name, '"') !== false))
	    {
		    message($lang_register['Name too short']);
            exit;
        }
	
	
	// check Usr id
	if ((strlen($user_id) < 5) || (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $user_id)) ||
		((strpos($user_id, '[') !== false || strpos($user_id, ']') !== false) && strpos($user_id, '\'') !== false && strpos($user_id, '"') !== false))
	    {
		    message($lang_register['User id too short']);
            exit;
        }
	
	
    $now = date("Y:m:d");

	// Add the user***************
        $query = 'INSERT INTO '.$db->prefix.'users (user_id, password, name, email, mobile, address, status, reg_time) VALUES(\''.$db->escape($user_id).'\', \''.md5($password).'\',\''.$name.'\', \''.$email.'\', \''.$mobile.'\', \''.$address.'\', \''.$status.'\',\''.$now.'\')';
	$db->query($query) or error('Unable to create user', __FILE__, __LINE__, $db->error());
	$new_uid = $db->insert_id();

	
	// Must the user verify the registration or do we log him/her in right now?
	// Load the template
	$mail_tpl = trim(file_get_contents(SBD_ROOT.'/pub/include/email_welcome.tpl'));

	// The first row contains the subject
	$first_crlf = strpos($mail_tpl, "\n");
	$mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
	$mail_message = trim(substr($mail_tpl, $first_crlf));

	$mail_subject = str_replace('<board_title>', $pun_config['o_site_title'], $mail_subject);
	$mail_message = str_replace('<name>', $name, $mail_message);
		
	$mail_message = str_replace('<user_id>', $user_id, $mail_message);
    $mail_message = str_replace('<password>', $password, $mail_message);
    $mail_message = str_replace('<email>', $email, $mail_message);
    
	$mail_message = str_replace('<board_mailer>', $pun_config['o_site_title'], $mail_message);
        //alamgir
	if(pun_mail($email, $mail_subject, $mail_message) != TRUE){
		message($lang_register['Reg e-mail error'].' <a href="mailto:'.$pun_config['o_admin_email'].'">'.$pun_config['o_admin_email'].'</a>.');
        exit;
    }
	else{
		message($lang_register['Reg e-mail success'].' <a href="mailto:'.$pun_config['o_admin_email'].'">'.$pun_config['o_admin_email'].'</a>.');
        exit;
    }	
}
//show registration form
else{
$page_title = $lang_register['Register'];
?>
<div id="main_full">
<h2><span><?php echo $lang_register['Register Title'] ?></span></h2>
	<div class="box">
	<form id="register" accept-charset="utf-8"  enctype="multipart/form-data" method="post" action="register.php?action=register">
	<div class="inform">
		<fieldset>
		<legend>&nbsp;<?php echo $lang_register['Basic Information'] ?>&nbsp;</legend>
		<div class="infldset">
			<table class="aligntop" cellspacing="1px" cellpadding="0">
				<tr> <th scope="row" align="right"><?php echo $lang_register['Name'] ?>:</th> 
					<td>
					<input type="hidden" name="form_sent" value="1" />
					<input type="text" name="req_name" id="req_name" size="45" maxlength="55"/> <span id="rsp_name" style="width:200px; height:18px"></span> 
					</td>
				</tr>
				<tr> <th scope="row" align="right"><?php echo $lang_register['User id'] ?>:</th> 
					<td>
					<input type="hidden" name="form_sent" value="1" />
					<input type="text" name="req_user_id" id="req_user_id" size="45" maxlength="55"/> <span id="rsp_user_id" style="width:200px; height:18px"></span> 
					</td>
				</tr>
				<tr> <th scope="row" align="right"><?php echo $lang_register['E-mail info'] ?>:</th> 
					<td>
					<input type="text" name="req_email" id="req_email" size="25" maxlength="25" /> <span id="rsp_email" style="width:200px; height:18px"> <?php echo $lang_register['E-mail note'] ?></span><br />
					</td>
				</tr>
				<tr> <th scope="row" align="right"><?php echo $lang_register['Password'] ?>:</th> 
					<td>
					<input type="password" name="req_password" id="req_email" size="25" maxlength="25" /> <span id="rsp_password" style="width:200px; height:18px"> <?php echo $lang_register['E-mail note'] ?></span><br />
					</td>
				</tr>			
			</table>      	
		</div>
		</fieldset>

		<fieldset>
		<legend>&nbsp;<?php echo $lang_register['Contact Information'] ?>&nbsp;</legend>
		<div class="infldset">
			<table class="aligntop" cellspacing="1px" cellpadding="0">
				<tr> <th scope="row" align="right"><?php echo $lang_register['Mobile no'] ?>:</th> 
					<td>
					<input type="text" name="req_mobile" id="req_mobile" size="25" maxlength="25" /> <span id="rsp_mobile" style="width:200px; height:18px"> <?php echo $lang_register['Mobile note'] ?></span><br />
					</td>
				</tr>
				
				<tr> <th scope="row" align="right"><?php echo $lang_register['Postal address'] ?>:</th> 
					<td>
					<textarea rows="4" cols="35" name="req_address" id="req_address">
					</textarea>
					</td>
				</tr>
			</table>      	
		</div>
		</fieldset>

		<fieldset>
		<legend>&nbsp;<?php echo $lang_register['Security'] ?>&nbsp;</legend>
		<div class="infldset">
			<table class="aligntop" cellspacing="1px" cellpadding="0">
				<tr> <th scope="row" align="right"><?php echo $lang_register['Answer this'] ?>:</th> 
					<td> 
					<?php  //choose a random question
					$quesno = rand(0, 4);
					echo $captcha_questions[$quesno];
					?>
					<input type="hidden" id="req_captcha_ques" name="req_captcha_ques" value="<?php echo $captcha_questions[$quesno];?>" />
					<select id="req_captcha_ans" name="req_captcha_ans">
					<?php
					foreach($captcha_answers as $ans){
					echo '<option value="'. $ans .'">'. $ans .'</option>';
                    }
					?>			
					</select> (Something that is naturally true.)
					</td>
				</tr>
			</table>      	
		</div>
		</fieldset>
		
	<p align="center"><input type="submit" name="register" id="submit" value="<?php echo $lang_register['Register'] ?>" /></p>
	</div> <!-- inform -->
	</form>
	</div> <!-- box -->
</div> <!-- block -->
<?php
include SBD_ROOT."/pub/footer.php";
}
// End the transaction
$db->end_transaction();
// Close the db connection (and free up any result data)
$db->close();
