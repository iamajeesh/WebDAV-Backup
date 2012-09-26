<?php
/**
 * backup.php; minimalistic webdav backup script.
 *
 * This script does a backup of a specified dir with
 * methods implemented in the webdav_client class.
 *
 * @author Mainboarder mainboarder.de
 * @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
 *
 *
 * Planned features:
 *  - encryption before transfering
 *  - autodelete after x days
 *  - just one mail with errors
 *  - external settings file
 *
 * Full paths are needed whenever you use this file es cronjob. So a local user executes the script
 * and a relative path will fail.
 */

if (!class_exists('webdav_client')) {
 require('/full/path/to/class_webdav_client.php');
}
$wdc = new webdav_client();

/**
 * Here are your specific settings needed
 */
$wdc->set_server('webdav.example.com');				// to which server should be connected
$wdc->set_user('username');							// your username to authenticate
$wdc->set_pass('password');							// your password to authenticate with the server
 
$pathLoc = '/path/to/local/backups/';				// which dir do you wish to backup
$pathExt = '/path/to/external/backup/collection/';	// where should this backup be transfered to

$mail = false;										// is mailsystem activated (php sendmail required)
$mailTo = 'admin@gmail.com';						// where should output go to

$wdc->set_ssl(1);									// is ssl required?
$wdc->set_port(443);								// on which port does the webdav server listen (ssl mostly 443, unencryptet 80)

// use HTTP/1.1
$wdc->set_protocol(1);								// which version should be used

// enable debugging
$wdc->set_debug(false);								// debugging on or off?
/**
 * End of settings
 */

// we try to connect
if (!$wdc->open()) {
  print 'Error: could not open server connection';
  exit;
}

// check if server supports webdav rfc 2518
if (!$wdc->check_webdav()) {
  print 'Error: server does not support webdav or user/password may be wrong';
  exit;
}

// create folder with date in scheme backup_31.01.2012-23.59
$date = date('d.m.Y-H.i',time());
$pathTod = $pathExt.'backup_'.$date.'/';

$httpStatusColl = $wdc->mkcol($pathTod);
print 'Todays Folder <backup_'.$date.'> webdav status: '.$httpStatusColl.'

';

print 'Datatransfer
';

// put a file to webdav collection
if (is_dir($pathLoc)) {
	if ($dh = opendir($pathLoc)) {
		$i = 0;														// set counter to zero
		while (($file = readdir($dh)) !== false) {					// as long as there are files
			$filename = $pathLoc.$file;

			if($file == '.' || $file == '..'){						// do nothing with dirs like . and ..
				$file='';
			} else {
				echo $file ."\n";									// where we are at the moment

				$handle = fopen ($filename, 'r');
				$contents = fread ($handle, filesize ($filename));
				fclose ($handle);
				$target_path = $pathTod.$file;
				$http_status = $wdc->put($target_path,$contents);
				print 'webdav server returns ' . $http_status .'
';																	// did it run as expected
				if($http_status != 201) {							// if not write it down
					$errorCode[$i] = $http_status;
					$errorFile[$i] = $file;
					$i++;											// set counter +1
				}
			}
		}
		closedir($dh);
		
		if($mail===true && !empty($errorCode)) {					// create mail if activated and errorcodes are available
			$i=0;													// set counter to zero again
			
			foreach($errorCode as &$code) {							// now create textsnippets
				$webdavErrCode .= $webdavErrCode . ' for file ' . $errorFile[$i] . '\n';
				$i++;
			}
			
			mail($mailTo, 'WebDAV Backup check needed', 'Hi,
please inspect your todays backup, there was a statuscode not 201 (ok), so there might be an error.
The status which was given back was: '.$webdavErrCode.'.

Kind regards,
your Backupcron.');													// send mail now
		}
	}
}

$wdc->close();														// finish and goodbye
flush();
