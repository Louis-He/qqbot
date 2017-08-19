<?php
include 'config.php';

function is_command($msg, $command) {
	if (substr(str_replace(" ", "", $msg), 0, strlen($command)) == $command) {
		return true;
	} else {
		return false;
	}
}

function record_exist($table, $key, $value) {
	$con = mysql_connect("localhost","root",getDBPassword());
	mysql_select_db("qqbot", $con);
	$result = mysql_query("select * from {$table} where {$key} = {$value}");
	$row = mysql_fetch_assoc($result);
	if ($row){
		return true;
	}else{
		return false;
	}
	mysql_close();
}

function get_coin($uid) {
	$con = mysql_connect("localhost","root",getDBPassword());
	mysql_select_db("qqbot", $con);
	$result = mysql_query("SELECT * FROM coin where `qq_id`='{$uid}'");
	while($row = mysql_fetch_array($result))
	{
	    $nowcoin = $row["coin"];
	}
	mysql_close();
	return (int)$nowcoin;
}
function update_coin($uid, $dcoin) {
	$con = mysql_connect("localhost","root",getDBPassword());
	mysql_select_db("qqbot", $con);
	if (record_exist("coin", "qq_id", $uid)) {
		
		$result = mysql_query("SELECT * FROM coin where `qq_id`='{$uid}'");
		while($row = mysql_fetch_array($result))
		  {
			  $nowcoin = $row["coin"];
		  }
		if ($nowcoin + $dcoin < 0) {
			return array("success" => false, "coin" => $nowcoin);
		} else {
			$result = mysql_query("UPDATE coin SET coin=coin+{$dcoin} WHERE `qq_id`='{$uid}'");
			return array("success" => true, "coin" => $nowcoin + $dcoin);
		}

	} else {
		if ($dcoin < 0) {
			$result = mysql_query("INSERT INTO coin VALUES ('{$uid}', '0');");
			return array("success" => false, "coin" => 0);
		} else {
			$result = mysql_query("INSERT INTO coin VALUES ('{$uid}', '{$dcoin}');");
			return array("success" => true, "coin" => $dcoin);
		}
	}
	mysql_close();
}

function sign($group_id, $qq_id) {
	$con = mysql_connect("localhost","root",getDBPassword());
	mysql_select_db("qqbot", $con);
	$result = mysql_query("select * from sign where qq_id = {$qq_id} and to_days(sign_time) = to_days(now());");
	$row = mysql_fetch_assoc($result);
	if ($row){
		return array("success" => false);
	}else{
		$dcoin = rand(1, 20);
		$updret = update_coin($qq_id, $dcoin);
		mysql_query("INSERT INTO sign VALUES ('{$group_id}', '{$qq_id}', '".date("Y-m-d H:i:s",time())."', '{$dcoin}');");
		return array("success" => true, "coin" => $updret["coin"], "addcoin" => $dcoin);
	}
	mysql_close();
}