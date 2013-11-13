<?php
/*
Plugin Name: Bleep Filter 2
Plugin URI: http://www.filterplugin.com
Description: A better word filter that passively removes unwanted words from your wordpress site by easily capturing common misspellings and deliberate obfuscation
Version: 1.0
Author: Nathan Lampe
Author URI: http://www.nathanlampe.com
License: GPL2
*/

class BleepFilter
{
	public function __construct(){
		require_once('wpadmin.class.php');
		$wpadmin = new WPAdmin;
		require_once('phoneticbleepfilter.class.php');
		$bleep_filter = new PhoneticBleepFilter;
	}
}

$bfp = new BleepFilter;

?>