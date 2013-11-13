<?php
/*
Plugin Name: Bleep Filter
Plugin URI: http://www.filterplugin.com
Description: A better word filter that passively removes unwanted words from your wordpress site by easily capturing common misspellings and deliberate obfuscation
Version: 1.0
Author: Nathan Lampe
Author URI: http://www.nathanlampe.com
License: GPL2
*/

/*  Copyright 2013  Nathan Lampe  (email : nathan@nathanlampe.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
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