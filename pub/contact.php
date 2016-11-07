<?php
$dir = dirname(dirname(__FILE__)); // location of  root dir
include $dir."/config.php";

require SBD_ROOT.'/pub/include/common.php';
// Load the register.php language file
require SBD_ROOT.'/pub/include/captcha.php';
define('CONTACT_PAGE', 1); // this define helps add appropriate header
include SBD_ROOT."/pub/header.php";

 if (isset($_POST['form_sent']))
 {
 	$captcha_ques = trim($_POST['req_captcha_ques']);
	$captcha_ans        = trim($_POST['req_captcha_ans']);
	
	//first check the captcha
	$res = strcmp($captcha_correct_ans[$captcha_ques], $captcha_ans);
	if( $res != 0)
		error($lang_register['Captcha wrong answer.'], 1);
	
				
	$subject = trim($_POST['contact_subject']);
	$email =  strtolower(trim($_POST['contact_email']));
	$mail_message = $_POST['message'];
				
	require SBD_ROOT.'/pub/include/email.php';

	if (!is_valid_email($email))
		error($lang_register['Invalid e-mail'],1);

	if(strlen($mail_message) < 100)
		error('Too short message.', 1);

	if(pun_mail($pun_config['o_admin_email'], $subject.'-'.$email, $message))
		echo '<p>Message has been sent. You will be replied shortly.</p>';
			 
} 
 else{
 	?>
	<div id="main_full">    	
	<!-- Main start -->
	<h2>Contact us</h2>
	<div style="height:10px;"> </div>
	<h3>Fill up the form</h3>
	<div id="container" style="width: 780px; height: 370px; margin: 0 auto;">
		<form method="post" accept-charset="utf-8"  action="contact.php">
		Subject: <input type="text" id="contact_subject" name="contact_subj"  style="width:250px; margin:10px;margin-left:33px;"/><br/>
		Your e-mail: <input type="text" id="contact_email" name="contact_email"  style="width:250px; margin:10px;"/><br/>
		Message: <br/>   <textarea rows="10" cols="35" name="message" id="message" style="margin-left:85px">
		</textarea>
		<br/> <br/> 
		Solve this quiz:
		<?php  //choose a random question
			$quesno = rand(0, 4);
			echo $captcha_questions[$quesno];
		?>
			<input type="hidden" name="form_sent" value="1" />
			<input type="hidden" id="req_captcha_ques" name="req_captcha_ques" value="<?php echo $captcha_questions[$quesno];?>" />
			<select id="req_captcha_ans" name="req_captcha_ans">
			<?php
			foreach($captcha_answers as $ans){
			echo '<option value="'. $ans .'">'. $ans .'</option>';
			}
			?>			
			</select> (Something that is naturally true.)
			<br/> <br/> 	
			<input type="submit" name="form_sent" id="form_sent" value="Send Message" style="width:100px; margin-left:85px"/>
			</form>
	</div>
		
	</div> <!-- main -->

<?php include SBD_ROOT.'/pub/footer.php'; 
}

// End the transaction
$db->end_transaction();

// Close the db connection (and free up any result data)
$db->close();

?>
		