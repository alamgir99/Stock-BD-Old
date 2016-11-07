<?php
//this single should contain ALL settings related tings
define('SBD_DEV', '1'); 

$dir = dirname(__FILE__); // this is the root directory

if(defined('SBD_DEV')){
	define('SBD_ROOT', $dir);
	$sb_config['o_base_url'] = 'http://localhost/stockbd/pub';   
}
else{
	define('SBD_ROOT', $dir);
	$sb_config['o_base_url'] = 'http://www.stock-bd.com';   
}

//trading related
$sb_tradingstart = 11; // user fraction like 11.5 to mean minutes
$sb_tradingend  = 15;
$sb_trading_days    = array('Sun','Mon','Tues','Wed','Thurs'); 
//set to false if there is a holiday or special day
$sb_market_open = true; 

//we should move this up to a trading calendar

$db_type = 'mysql';
$db_host = 'localhost';
$db_name = 'stockbd';
$db_username = 'stockbd';
$db_password = 'sbttxy99';
$db_prefix = '';
$p_connect = false;

$cookie_name = 'stockbd_cookie';
$cookie_domain = '';
$cookie_path = '/';
$cookie_secure = 0;
$cookie_seed = 'd8ca5137a0211a5b';

define('PUN', 1);  //this defin ensure some files are not called directly

$sb_config['o_regs_allow'] = '0';
$sb_config['o_img_dir'] = 'uploads';
$sb_config['o_admin_email'] = 'stockbd@gmail.com';
$sb_config['o_site_title'] = 'Stock BD: Premium data source';
$sb_config['o_save_pass'] = '1';
$sb_config['o_member_only'] = false; // set to true to make everything member only
$sb_config['Mailer'] = 'Stock-BD.com';

//admin users- add them here
$users = array("admin" => md5("admin"), 
			"reg" => md5("reg")
			);
            
//this is very important            
//date_default_timezone_set("Asia/Dhaka"); 
// no error/warning reporting
error_reporting(0);
