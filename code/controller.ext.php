<?php
/**
 * Controller for Sencypt Module for sentora 1.0.3
 * Based off of Cer_Manager module by Diablo925
 * Version : 001
 */
 
class module_controller extends ctrl_module {
		
	static $ok;
	static $error;
	static $delok;
	static $keyadd;
	static $download;
	static $empty;
	
	static function ExecuteDownload($domain, $username) {
		set_time_limit(0);
		global $zdbh, $controller;
		//$domain = str_replace('.', '_', $domain);
		$temp_dir = ctrl_options::GetSystemOption('sentora_root') . "etc/tmp/";
		$homedir = "../../../etc/letsencrypt/live/";
		$backupname = $domain;
		$resault = exec("cd " . $homedir . $domain ."/ && " . ctrl_options::GetSystemOption('zip_exe') . " -r9 " . $temp_dir . $backupname . " *");
		@chmod($temp_dir . $backupname . ".zip", 0777);
		$filename = $backupname . ".zip";
		$filepath = $temp_dir;
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"".$filename."\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".filesize($filepath.$filename));
		ob_end_flush();
		readfile($filepath.$filename);
		unlink($temp_dir . $backupname . ".zip");
		return true;
	}
	
	static function doDownload() {
		global $controller;
		$currentuser = ctrl_users::GetUserDetail();
		$formvars = $controller->GetAllControllerRequests('FORM');
		if (self::ExecuteDownload($formvars['inName'], $currentuser["username"]))
		return true;
	}
	
	static function doDelete() {
		global $controller;
		runtime_csfr::Protect();
		$currentuser = ctrl_users::GetUserDetail();
		$formvars = $controller->GetAllControllerRequests('FORM');
		if (self::ExecuteDelete($formvars['inName'], $currentuser["username"]))
		return true;
	}
	
	static function ExecuteDelete($domain, $username) {
		global $zdbh, $controller;
		$currentuser = ctrl_users::GetUserDetail();
		//$sslFolder = str_replace('.', '_', $domain);
		$dir = "../../../etc/letsencrypt/live/".$domain."/";
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				unlink($dir."/".$object);
			}
		 }
		// will this attempt to remove the entire "/etc/letsencrypt/live/" path?
		rmdir($dir);
		
		if($domain == ctrl_options::GetSystemOption('sentora_domain')) {
			$name = 'global_zpcustom';
			$new = '';
	
			$line = "# Made from sencrypt start".fs_filehandler::NewLine();
			$line  .= fs_filehandler::NewLine();
			$line .= 'SSLEngine On' .fs_filehandler::NewLine();
			$line .= "SSLCertificateFile /etc/letsencrypt/live/".$domain."/cert.pem".fs_filehandler::NewLine();
			$line .= "SSLCertificateKeyFile /etc/letsencrypt/live/".$domain."/privkey.pem".fs_filehandler::NewLine();
			$line .= "SSLCACertificateFile /etc/letsencrypt/live/".$domain."/crtchain.pem".fs_filehandler::NewLine();
			$line .= "SSLProtocol All -SSLv2 -SSLv3".fs_filehandler::NewLine();
			$line .= "SSLHonorCipherOrder on".fs_filehandler::NewLine();
			$line .= "SSLCipherSuite \"EECDH+ECDSA+AESGCM EECDH+aRSA+AESGCM EECDH+ECDSA+SHA384 EECDH+ECDSA+SHA256 EECDH+aRSA+SHA384 EECDH+aRSA+SHA256 EECDH+AESGCM EECDH EDH+AESGCM EDH+aRSA HIGH !MEDIUM !LOW !aNULL !eNULL !LOW !RC4 !MD5 !EXP !PSK !SRP !DSS\"".fs_filehandler::NewLine();
			$line .= fs_filehandler::NewLine();
			$line .= "# Made from sencrypt end".fs_filehandler::NewLine();
			
			$sql = $zdbh->prepare("UPDATE x_settings SET so_value_tx = replace(so_value_tx, :data, :new) WHERE so_name_vc = :name");
			$sql->bindParam(':data', $line);
			$sql->bindParam(':new', $new);
			$sql->bindParam(':name', $name);
			$sql->execute();
			
			$line = "# Made from sencrypt start".fs_filehandler::NewLine();
			$line  .= fs_filehandler::NewLine();
			$line .= 'SSLEngine On' .fs_filehandler::NewLine();
			$line .= "SSLCertificateFile /etc/letsencrypt/live/".$domain."/cert.pem".fs_filehandler::NewLine();
			$line .= "SSLCertificateKeyFile /etc/letsencrypt/live/".$domain."/privkey.pem".fs_filehandler::NewLine();
			$line .= "SSLProtocol All -SSLv2 -SSLv3".fs_filehandler::NewLine();
			$line .= "SSLHonorCipherOrder on".fs_filehandler::NewLine();
			$line .= "SSLCipherSuite \"EECDH+ECDSA+AESGCM EECDH+aRSA+AESGCM EECDH+ECDSA+SHA384 EECDH+ECDSA+SHA256 EECDH+aRSA+SHA384 EECDH+aRSA+SHA256 EECDH+AESGCM EECDH EDH+AESGCM EDH+aRSA HIGH !MEDIUM !LOW !aNULL !eNULL !LOW !RC4 !MD5 !EXP !PSK !SRP !DSS\"".fs_filehandler::NewLine();
			$line .= fs_filehandler::NewLine();
			$line .= "# Made from sencrypt end".fs_filehandler::NewLine();
			
			$sql = $zdbh->prepare("UPDATE x_settings SET so_value_tx = replace(so_value_tx, :data, :new) WHERE so_name_vc = :name");
			$sql->bindParam(':data', $line);
			$sql->bindParam(':new', $new);
			$sql->bindParam(':name', $name);
			$sql->execute();
					
			$portname = "sentora_port";
			$port = "80";
			$updatesql = $zdbh->prepare("UPDATE x_settings SET so_value_tx = :value WHERE so_name_vc = :name");
			$updatesql->bindParam(':value', $port);
			$updatesql->bindParam(':name', $portname);
			$updatesql->execute();
			
		} else {

			$port = NULL;
			$portforward = NULL;
			$new = '';
						
			$line = "# Made from sencrypt start".fs_filehandler::NewLine();
			$line .= fs_filehandler::NewLine();
			$line .= 'SSLEngine On' .fs_filehandler::NewLine();
			$line .= "SSLCertificateFile /etc/letsencrypt/live/".$domain."/cert.pem".fs_filehandler::NewLine();
			$line .= "SSLCertificateKeyFile /etc/letsencrypt/live/".$domain."/privkey.pem".fs_filehandler::NewLine();
			$line .= "SSLCACertificateFile /etc/letsencrypt/live/".$domain."/crtchain.pem".fs_filehandler::NewLine();
			$line .= "SSLProtocol All -SSLv2 -SSLv3".fs_filehandler::NewLine();
			$line .= "SSLHonorCipherOrder on".fs_filehandler::NewLine();
			$line .= "SSLCipherSuite \"EECDH+ECDSA+AESGCM EECDH+aRSA+AESGCM EECDH+ECDSA+SHA384 EECDH+ECDSA+SHA256 EECDH+aRSA+SHA384 EECDH+aRSA+SHA256 EECDH+AESGCM EECDH EDH+AESGCM EDH+aRSA HIGH !MEDIUM !LOW !aNULL !eNULL !LOW !RC4 !MD5 !EXP !PSK !SRP !DSS\"".fs_filehandler::NewLine();
			$line .= fs_filehandler::NewLine();
			$line .= "# Made from sencrypt end".fs_filehandler::NewLine();

			$sql = $zdbh->prepare("UPDATE x_vhosts SET vh_custom_tx = replace(vh_custom_tx, :data, :new), vh_custom_port_in=:port, vh_portforward_in=:portforward WHERE vh_name_vc = :domain");
			 
			$sql->bindParam(':data', $line);
			$sql->bindParam(':new', $new);
			$sql->bindParam(':domain', $domain);
			$sql->bindParam(':port', $port);
			$sql->bindParam(':portforward', $portforward);
			$sql->execute();
				
			$line = "# Made from sencrypt start".fs_filehandler::NewLine();
			$line .= fs_filehandler::NewLine();
			$line .= 'SSLEngine On' .fs_filehandler::NewLine();
			$line .= "SSLCertificateFile /etc/letsencrypt/live/".$domain."/cert.pem".fs_filehandler::NewLine();
			$line .= "SSLCertificateKeyFile /etc/letsencrypt/live/".$domain."/privkey.pem".fs_filehandler::NewLine();
			$line .= "SSLProtocol All -SSLv2 -SSLv3".fs_filehandler::NewLine();
			$line .= "SSLHonorCipherOrder on".fs_filehandler::NewLine();
			$line .= "SSLCipherSuite \"EECDH+ECDSA+AESGCM EECDH+aRSA+AESGCM EECDH+ECDSA+SHA384 EECDH+ECDSA+SHA256 EECDH+aRSA+SHA384 EECDH+aRSA+SHA256 EECDH+AESGCM EECDH EDH+AESGCM EDH+aRSA HIGH !MEDIUM !LOW !aNULL !eNULL !LOW !RC4 !MD5 !EXP !PSK !SRP !DSS\"".fs_filehandler::NewLine();
			$line .= fs_filehandler::NewLine();
			$line .= "# Made from sencrypt end".fs_filehandler::NewLine();
	
			$sql = $zdbh->prepare("UPDATE x_vhosts SET vh_custom_tx = replace(vh_custom_tx, :data, :new), vh_custom_port_in=:port, vh_portforward_in=:portforward WHERE vh_name_vc = :domain");
			 
			$sql->bindParam(':data', $line);
			$sql->bindParam(':new', $new);
			$sql->bindParam(':domain', $domain);
			$sql->bindParam(':port', $port);
			$sql->bindParam(':portforward', $portforward);
			$sql->execute();
		}
		self::SetWriteApacheConfigTrue();
		self::$delok = true;
		return true;
	}

	static function doMakenew() {
		global $controller;
		runtime_csfr::Protect();
		$currentuser = ctrl_users::GetUserDetail();
		$formvars = $controller->GetAllControllerRequests('FORM');
		if (empty($formvars['inDomain'])) { 
			self::$empty = true;
			return false;
		}
		if (self::ExecuteMakessl($formvars['inDomain']))
			return true;
	}
	
	static function ExecuteMakessl($domain) {
		global $zdbh;
		global $controller;
		$currentuser = ctrl_users::GetUserDetail();
		$formvars = $controller->GetAllControllerRequests('FORM');
		$sslFolder = str_replace('.', '_', $domain);
		
		if (!is_dir("../../../etc/letsencrypt/live/".$domain."/")) {
				mkdir("../../../etc/letsencrypt/live/".$domain."/", 0777);
		}
		if (!is_dir("../../../etc/letsencrypt/live/".$domain."/")) {
				mkdir("../../../etc/letsencrypt/live/".$domain."/", 0777);
		} else {
			self::$error = true;
			return false;
		}
			
		// use let's encrypt create SSL command:
		// COMMAND LINE: ./certbot-auto certonly --standalone -d domain.com
		$command = ctrl_options::GetSystemOption('zsudo');
		  // stop apache
		$args = array("service", ctrl_options::GetSystemOption('apache_sn'), "stop");
		$returnValue = ctrl_system::systemCommand($command, $args);
		  // create cert
		$returnValue = ctrl_system::systemCommand($command, "./certbot-auto certonly --standalone -d ".$domain);
		  // start apache
		$args = array("service", ctrl_options::GetSystemOption('apache_sn'), "start");

		if ($domain == ctrl_options::GetSystemOption('sentora_domain')) {
			$line = "# Made from sencrypt start".fs_filehandler::NewLine();
			$line .= fs_filehandler::NewLine();
			$line .= 'SSLEngine On' .fs_filehandler::NewLine();
			$line .= "SSLCertificateFile /etc/letsencrypt/live/".$domain."/cert.pem".fs_filehandler::NewLine();
			$line .= "SSLCertificateKeyFile /etc/letsencrypt/live/".$domain."/privkey.pem".fs_filehandler::NewLine();
			$line .= "SSLProtocol All -SSLv2 -SSLv3".fs_filehandler::NewLine();
			$line .= "SSLHonorCipherOrder on".fs_filehandler::NewLine();
			$line .= "SSLCipherSuite \"EECDH+ECDSA+AESGCM EECDH+aRSA+AESGCM EECDH+ECDSA+SHA384 EECDH+ECDSA+SHA256 EECDH+aRSA+SHA384 EECDH+aRSA+SHA256 EECDH+AESGCM EECDH EDH+AESGCM EDH+aRSA HIGH !MEDIUM !LOW !aNULL !eNULL !LOW !RC4 !MD5 !EXP !PSK !SRP !DSS\"".fs_filehandler::NewLine();
			$line .= fs_filehandler::NewLine();
			$line .= "# Made from sencrypt end".fs_filehandler::NewLine();
			
			$name = 'global_zpcustom';
            $sql = $zdbh->prepare("SELECT * FROM x_settings WHERE so_name_vc  = :name");
            $sql->bindParam(':name', $name);
            $sql->execute();
            while ($row = $sql->fetch()) {
				$olddata = $row['so_value_tx'];
			}
			$data = $olddata.$line;
			
			$updatesql = $zdbh->prepare("UPDATE x_settings SET so_value_tx = :value WHERE so_name_vc = :name");
			$updatesql->bindParam(':value', $data);
			$updatesql->bindParam(':name', $name);
			$updatesql->execute();
			$portname = "sentora_port";
			$port = "443";
			$updatesql = $zdbh->prepare("UPDATE x_settings SET so_value_tx = :value WHERE so_name_vc = :name");
			$updatesql->bindParam(':value', $port);
			$updatesql->bindParam(':name', $portname);
			$updatesql->execute();
		
		} else {
			
			$line = "# Made from sencrypt start".fs_filehandler::NewLine();
			$line .= fs_filehandler::NewLine();
			$line .= 'SSLEngine On' .fs_filehandler::NewLine();
			$line .= "SSLCertificateFile /etc/letsencrypt/live/".$domain."/cert.pem".fs_filehandler::NewLine();
			$line .= "SSLCertificateKeyFile /etc/letsencrypt/live/".$domain."/privkey.pem".fs_filehandler::NewLine();
			$line .= "SSLProtocol All -SSLv2 -SSLv3".fs_filehandler::NewLine();
			$line .= "SSLHonorCipherOrder on".fs_filehandler::NewLine();
			$line .= "SSLCipherSuite \"EECDH+ECDSA+AESGCM EECDH+aRSA+AESGCM EECDH+ECDSA+SHA384 EECDH+ECDSA+SHA256 EECDH+aRSA+SHA384 EECDH+aRSA+SHA256 EECDH+AESGCM EECDH EDH+AESGCM EDH+aRSA HIGH !MEDIUM !LOW !aNULL !eNULL !LOW !RC4 !MD5 !EXP !PSK !SRP !DSS\"".fs_filehandler::NewLine();
			$line .= fs_filehandler::NewLine();
			$line .= "# Made from sencrypt end".fs_filehandler::NewLine();
			
			$port 			= "443";
			$portforward 	= "1";
			
            $sql = $zdbh->prepare("SELECT * FROM x_vhosts WHERE vh_name_vc = :domain AND vh_deleted_ts IS NULL");
            $sql->bindParam(':domain', $domain);
            $sql->execute();
            while ($row = $sql->fetch()) { $olddata = $row['vh_custom_tx']; }
			$data = $olddata.$line;
			
        	$sql = $zdbh->prepare("UPDATE x_vhosts SET vh_custom_tx=:data, vh_custom_port_in=:port, vh_portforward_in=:portforward WHERE vh_name_vc = :domain");
        	$sql->bindParam(':data', $data);
			$sql->bindParam(':domain', $domain);
			$sql->bindParam(':port', $port);
			$sql->bindParam(':portforward', $portforward);
        	$sql->execute();
		}
		self::SetWriteApacheConfigTrue();
		self::$ok = true;	
		return true;	
	}

	static function ListDomains($uid) {
		global $zdbh, $controller;
		$currentuser = ctrl_users::GetUserDetail($uid);
		$sql = "SELECT * FROM x_vhosts WHERE vh_acc_fk=:userid AND vh_enabled_in=1 AND vh_deleted_ts IS NULL ORDER BY vh_name_vc ASC";
		$numrows = $zdbh->prepare($sql);
		$numrows->bindParam(':userid', $currentuser['userid']);
		$numrows->execute();
		if ($numrows->fetchColumn() <> 0) {
			$sql = $zdbh->prepare($sql);
			$sql->bindParam(':userid', $currentuser['userid']);
			$res = array();
			$sql->execute();
			if($currentuser["username"] == "zadmin") {
				$name = ctrl_options::GetSystemOption('sentora_domain');
				$res[] = array('domain' => "$name");
			}
			while ($rowdomains = $sql->fetch()) {
				$res[] = array('domain' => $rowdomains['vh_name_vc']);
			}
			return $res;
		} else {
			return false;
		}
	}
	
	static function getDomainList() {
		$currentuser = ctrl_users::GetUserDetail();
		return self::ListDomains($currentuser['userid']);
	}
// error here... need to fix this function first!
	static function ListSSL($uname) {
		global $controller;
		// need to cross reference user's domains with matching ssl domain folders use a for-each?
		//foreach ($domain as $folder) // example

		$letsEncriptCerts = "../../../etc/letsencrypt/live/";
		
		echo "<br>";
			if (!is_dir($letsEncriptCerts)) {
				mkdir($letsEncriptCerts, 0777);
			}
			//$dir = "/etc/letsencrypt/live/";
			if(substr($letsEncriptCerts, -1) != "/") $letsEncriptCerts .= "/";
			$d = @dir($letsEncriptCerts);
			while(false !== ($entry = $d->read())) {
				//$entry1 = str_replace('_', '.', $entry);
				if($entry[0] == ".") continue;
				$retval[] = array("name" => "$entry");
			}
		$d->close();
		return $retval;
	//} //end for each
	}

	static function getSSLList() {
		$currentuser = ctrl_users::GetUserDetail();
		return self::ListSSL($currentuser['username']);
	}

	static function SetWriteApacheConfigTrue() {
		global $zdbh;
		$sql = $zdbh->prepare("UPDATE x_settings
								SET so_value_tx='true'
								WHERE so_name_vc='apache_changed'");
		$sql->execute();
	}

	static function getResult() {
		if (self::$ok) {
			return ui_sysmessage::shout(ui_language::translate("Your FREE SSL Certificate has been made. It will be ready in about 5 minutes."), "zannounceok");
		}
		if (self::$delok) {
			return ui_sysmessage::shout(ui_language::translate("The selected certificate has been deleted."), "zannounceerror");
		}
		if (self::$error) {
			return ui_sysmessage::shout(ui_language::translate("A certificate with that name already exists."), "zannounceerror");
		}
		if (self::$empty) {
			return ui_sysmessage::shout(ui_language::translate("An empty field is not allowed."), "zannounceerror");
		}
		// remove
		if (self::$keyadd) {
			return ui_sysmessage::shout(ui_language::translate("Certificate Signing Request was made and sent to the mail you have entered"), "zannounceok");
		}
		return;
	}

    static function getCopyright() {
        $copyright = '<font face="ariel" size="2">'.ui_module::GetModuleName().' v0.0.1 &copy; 2016-'.date("Y").' by <a target="_blank" href="http://forums.sentora.org/member.php?action=profile&uid=2">TGates</a> for <a target="_blank" href="http://sentora.org">Sentora Control Panel</a>&nbsp;&#8212;&nbsp;Help support future development of this module and donate today!</font>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="DW8QTHWW4FMBY">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" width="70" height="21" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>';
        return $copyright;
    }
}
?>