<?php
	$req = $_GET["keyword"];
	$uid = $_GET["uid"];
	$method = $_GET["method"];
	$groupid = $_GET["groupid"];
	include 'function.php';
	
	if ($method == "forbid") {
		if (record_exist("can_forbid", "group_id", $groupid)) {
			$updret = update_coin($uid, -10);
			if ($updret["success"] == true) {
				if (strstr($req, "[@") == false) {
					echo json_encode(array("allow"=>"false", "msg"=>"请求格式不正确."));
				} else {
					$target = str_replace("禁言", "", str_replace("[@", "", str_replace(" ", "", str_replace("]", "", $req))));
					echo json_encode(array("allow"=>"true", "qq_id"=>$target, "msg"=>"扣费成功, 您目前剩余".$updret["coin"]."硬币。[@{$target}]将立即被禁言10分钟。"));
				}
			} else {
				echo json_encode(array("allow"=>"false", "msg"=>"该功能需要10硬币, 您的硬币不够..."));
			}
		} else {
			echo json_encode(array("allow"=>"false", "msg"=>"对不起, doge在该群无此权限"));
		}
		
		exit(0);
	}
	
	if (is_command($req, "!radar")) {
		//雷达图
		$now = strtotime(date("Y-m-d h:i:s")) + 4 * 60 * 60 - 10 * 60;
		$y = date("Y", $now);
		$m = date("m", $now);
		$d = date("d", $now);
		$h = str_pad(date("H", $now),2,"0",STR_PAD_LEFT);
		$i = str_pad((max(round(date("i", $now)/6)-1, 0))*6,2,"0",STR_PAD_LEFT);
		$dt = "$y/$m/$d";
		$tm = "$y$m$d$h$i";
		$url = "http://image.nmc.cn/product/$dt/RDCP/medium/SEVP_AOC_RDCP_SLDAS_EBREF_AZ9210_L88_PI_{$tm}00000.PNG";
		echo json_encode(array("type"=>"img", "url"=>$url));
	} elseif ((substr($req, -strlen("温度"))=='温度')  || (substr($req, -strlen("气温"))=='气温')) {
		//气象站温度
		$sta = str_replace(" ", "", $req);
		$sta = str_replace("温度", "", $sta);
		$sta = str_replace("气温", "", $sta);
		$sta = str_replace("站", "", $sta);
		
		$code = file_get_contents("http://weixin.shkeylab-mh.org/weather_forecasts/".urlencode($sta));
		$code = substr($code, strpos($code, '<div class="tempe text-center">')+strlen('<div class="tempe text-center">'));
		$temp = (float)substr($code, 0, strpos($code, '<span class="degree">'));
		if ($temp==0) {
			$temp = "对不起，没有这个气象站。";
		} else {
			$temp = "{$sta}气象站: 当前温度{$temp}℃";
		}
		echo json_encode(array("type"=>"text", "msg"=>$temp));
	} elseif ((substr($msg, 0, strlen("预警"))=='预警') || (substr($msg, 0, strlen("预警机"))=='预警机') || (substr($msg, 0, strlen("信号"))=='信号')){
		//查询实时预警信号
		$js = curl("http://182.254.214.114/wxapp/jsondata/fqwarnlist.js");
		$js = str_replace("var fqwarns=","",$js);
		$js = str_replace("var warns=","",$js);
		$js = str_replace("var fqhistorywarns=[]","@",$js);
		$fq = json_decode(substr($js, 0, strpos($js, "@")), true);
		$sh = json_decode(substr($js, strpos($js, "@")+1), true);
		foreach($sh as $warn) {
			$ret = str_replace("<br>", "", $warn["htmlword"]);
			$ret = str_replace("<br/>", "", $ret);
			$ret = substr($ret, 0, strpos($ret, "防御"));
			$ret = str_replace("发布:", "发布".$warn['name']."预警信号:", $ret);
			$ret = $warn['yjid']." ".$warn['name'].'预警：'.$ret;
			//curl_setopt($ch, CURLOPT_URL, "http://localhost:3200/send?type=group&to=$gid&msg=$ret");
			$ret = urlencode($ret);
			echo json_encode(array("type"=>"text", "msg"=>$ret));
			//echo "curl \"http://localhost:3200/send?type=group&to=$gid&msg=$ret\"";
		}
	}elseif (is_command($req, "!sign")) {
		//签到功能
		$ret = sign($groupid, $uid);
		if ($ret["success"] == true) {
			$json = array("type"=>"text", "msg"=>"签到成功, 获得".$ret["addcoin"]."硬币, 现在共有".$ret["coin"]."硬币");
		} else {
			$json = array("type"=>"text", "msg"=>"您今天已经签过到啦w");
		}
		echo json_encode($json);
	} elseif (is_command($req, "!coin")) {
		//查询硬币
		$json = array("type"=>"text", "msg"=>"您还有".get_coin($uid)."硬币");
		echo json_encode($json);
	} else {
		//智能自动回复
		$url = "http://www.tuling123.com/openapi/api";
		$post_data = json_encode(array ("key" => "6380808b6e454953931ea750ea0a4d5b","info" => $req,"userid" => $uid));
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json; charset=utf-8'
			)
		);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		$output = json_decode(curl_exec($ch), true);
		curl_close($ch);
		echo json_encode(array("type"=>"text", "msg"=>$output["text"]));
	}
