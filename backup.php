<?php
/**
 * test.php; mininimalistic class webdav_client testing script.
 *
 * This script shows the basic use of the methods implemented in the webdav_client class.
 *
 * @author Christian Juerges <christian.juerges@xwave.ch>, Xwave GmbH, Josefstr. 92, 8005 Zuerich - Switzerland.
 * @copyright (C) 2003/2004, Christian Juerges
 * @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
 * @package test
 */ 
/*
$Id: test.php,v 1.4 2004/08/18 14:11:04 chris Exp $
$Author: chris $
$Date: 2004/08/18 14:11:04 $
$Revision: 1.4 $

*/

if (!class_exists('webdav_client')) {
 require('/full/path/to/class_webdav_client.php');
} 

$wdc = new webdav_client();
$wdc->set_server('webdav.example.com');
$wdc->set_ssl(1);
$wdc->set_port(443);
$wdc->set_user('user');
$wdc->set_pass('password');
// use HTTP/1.1
$wdc->set_protocol(1);
// enable debugging
$wdc->set_debug(false);


if (!$wdc->open()) {
  print 'Error: could not open server connection';
  exit;
}

// check if server supports webdav rfc 2518
if (!$wdc->check_webdav()) {
  print 'Error: server does not support webdav or user/password may be wrong';
  exit;
}
?>

Dateitransfer
<?php
$pfadLoc = '/local/full/path/backups/';
$pfadExt = '/external/full/path/backups/';

//create folder with date
$date = date('d.m.Y-H.i',time());
$pfadHeute = $pfadExt.'backup_'.$date.'/';

$httpStatus = $wdc->mkcol($pfadHeute);
print 'Todays Folder creating: '.$httpStatus.'
';

//put a file to webdav collection
if (is_dir($pfadLoc)) {
	if ($dh = opendir($pfadLoc)) {
		while (($file = readdir($dh)) !== false) {
			$filename = $pfadLoc.$file;

			if($file == '.' || $file == '..'){
				$file='';
			} else {
				echo $file ."\n";

				$handle = fopen ($filename, 'r');
				$contents = fread ($handle, filesize ($filename));
				fclose ($handle);
				$target_path = $pfadHeute.$file;
				$http_status = $wdc->put($target_path,$contents);
				print 'webdav server returns ' . $http_status .'
';
			}
		}
		closedir($dh);
	}
}

$wdc->close();
flush();
?>