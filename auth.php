<?php

/*************************************************

Module: Auth Class
Author: Ryan Barril
Created: Sep. 29, 2020
Dependent: base,db,paginate

**************************************************/

class auth{
	
	var $ip,$tbl,$notification,$clss,$id;

	function __construct(){
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->tbl = "users";
		$this->clss = "user";
		$this->notification = '';
		$this->id = 1;
	}

	function login($user,$pass){
		$tbl = $this->tbl;
		$pass = MD5(strCl($pass));
		$user = $user;
		$sql = "SELECT COUNT(*) as c FROM `{$tbl}` LEFT JOIN team ON `{$tbl}`.group = team.code WHERE  (`{$tbl}`.uname = '{$user}' OR `{$tbl}`.email = '{$user}') AND `{$tbl}`.`pass` = '{$pass}' AND `{$tbl}`.`active` = 1 AND `team`.`enabled` = 1 LIMIT 1;";
		$r = qarr($sql,1);

		if($r <= 0){
			$this->notification = "Invalid username or password.";
			lurl("index.php");
		} else {
			$token = rndchr(20);
			$sql = "UPDATE `{$tbl}` SET token = '{$token}' WHERE (uname = '{$user}' OR email = '{$user}') AND pass = '{$pass}' AND active = 1 LIMIT 1;";
			$tmp = qexec($sql);
			
			$sql = "SELECT `{$tbl}`.*,team.id AS grpid FROM `{$tbl}` LEFT JOIN team ON `{$tbl}`.`group` = `team`.`code` WHERE  (`{$tbl}`.`uname` = '{$user}' OR `{$tbl}`.`email` = '{$user}') AND `{$tbl}`.pass = '{$pass}' AND `team`.`enabled` = 1 LIMIT 1;";
			$user = qarr($sql,2);

			$setting_p_internal = FALSE;
			$setting_p_audited = FALSE;

			setDENV('setting_p_internal', $setting_p_internal, time()+3600);
			setDENV('setting_p_audited', $setting_p_audited, time()+3600);
			
			setDENV('token', $token, time()+3600);
			setDENV('level', $user['level'], time()+3600);
			setDENV('role', $user['role'], time()+3600);
			setDENV('grp', $user['group'], time()+3600);

			$package = getPackageSetup($user['group']);

			setDENV('p_users', $package['users'], time()+3600);
			setDENV('p_project', $package['project'], time()+3600);
			setDENV('p_audited', $package['audited'], time()+3600);
			setDENV('p_internal', $package['internal'], time()+3600);

			$history = new history();
			$pid = $user['id'];
			$gc = $user['group'];
			$pclass = "user";
			$action = "User log-in.";
			$details = "The user has log-in to the system.";
			$history->set($pid,$gc,$pclass,$action,$details);
			$history->store();

			lurl("dashboard.php");
		}
	}

	function logout($type = 0){
		
		$tokenID = getDENV('token');
		$usr = new user();
		$user = $usr->get($tokenID);
		
		$history = new history();
		$pid = $user['id'];
		$pclass = "user";
		$gc = $user['group'];
		$action = "User log-off.";
		$details = "The user has log-off to the system.";
		$history->set($pid,$gc,$pclass,$action,$details);
		$history->store();
		
		$_SESSION['token'] = 'x';
		setcookie('token','x',time());
		if($type == 0){
			lurl("index.php");
		}
	}

	function check(){
		$tokenID = getDENV('token');

		$tbl = $this->tbl;
		$sql = "SELECT COUNT(*) as c FROM `{$tbl}` LEFT JOIN `team` ON `{$tbl}`.`group` = `team`.`code` WHERE `active` = 1 AND token = '{$tokenID}' AND `team`.`enabled` = 1 LIMIT 1;";
		
		$valid = qarr($sql,1);

		if($valid > 0){
			return false;
		} else {
			lurl("index.php");
		}
	}
}	

?>
