<?php

/**
 * Plugin Name: Magic Login Mail
 * Description: Enter your email address, and send you an email with a magic link to login without a password.
 * Version: 1.03
 * Author: Katsushi Kawamori
 * Author URI: https://riverforest-wp.info/
 * Text Domain: magic-login-mail
 *
 * @package Magic Login Mail
 */

/*
	Copyright (c) 2021- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

!defined('MAGICLOGINAPI_PATH') && define('MAGICLOGINAPI_PATH', plugin_dir_path(__FILE__));

/**
 * Write an entry to a log file in the uploads directory.
 * 
 * @since x.x.x
 * 
 * @param mixed $entry String or array of the information to write to the log.
 * @param string $file Optional. The file basename for the .log file.
 * @param string $mode Optional. The type of write. See 'mode' at https://www.php.net/manual/en/function.fopen.php.
 * @return boolean|int Number of bytes written to the lof file, false otherwise.
 */
if (!function_exists('magiclogin_log')) {
	function magiclogin_log($entry, $mode = 'a', $file = 'magiclogin_log')
	{
		// Get WordPress uploads directory.
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];
		// If the entry is array, json_encode.
		if (is_array($entry)) {
			$entry = json_encode($entry);
		}
		// Write the log file.
		$file  = $upload_dir . '/' . $file . '.log';
		$file  = fopen($file, $mode);
		$bytes = fwrite($file, current_time('mysql') . "::" . $entry . "\n");
		fclose($file);
		return $bytes;
	}
}

require "admin/admin-setting.php";

if (!class_exists('MagicLoginMail')) {
	require_once(dirname(__FILE__) . '/lib/class-magicloginmail.php');
}

if (!class_exists('MagicLoginAPI')) {
	require_once(dirname(__FILE__) . '/lib/class-magicloginapi.php');
}
