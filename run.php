<?php
/**
 *
 * PLEASE DON'T CHANGE THIS **
 *
 * PayPal eMail Validator 2.0
 * Author: Faiz Ainurrofiq (https://paisx.net/)
 * Link: https://github.com/paisx/
 *
 **/

ob_implicit_flush();
date_default_timezone_set("Asia/Jakarta");
define("OS", strtolower(PHP_OS));

echo banner();
enterlist:
$listname = readline(" Enter list: ");
if(empty($listname) || !file_exists($listname)) {
	echo" [?] list not found".PHP_EOL;
	goto enterlist;
}
$lists = array_unique(explode("\n", str_replace("\r", "", file_get_contents($listname))));
$delim = readline(" Delim (fill if the list type is empass)? ");
$delim = empty($delim) ? false : $delim;
$savetodir = readline(" Save to dir (default: valid)? ");
$savetodir = empty($savetodir) ? "valid" : $savetodir;
if(!is_dir($savetodir)) mkdir($savetodir);
chdir($savetodir);
sendemail:
$ratio = readline(" Send email per second? (*max 50) ");
$ratio = (empty($ratio) || !is_numeric($ratio) || $ratio <= 0) ? 2 : $ratio;
if($ratio > 50) {
	echo "* max 50".PHP_EOL;
	goto sendemail;
}
$delpercheck = readline(" Delete list per check (y/n)? ");
$delpercheck = strtolower($delpercheck) == "y" ? true : false;
$no = 0; $total = count($lists); $registered = 0; $die = 0; $limited = 0;
$lists = array_chunk($lists, $ratio);
echo PHP_EOL;

foreach($lists as $clist) {
	$array = $ch = array();
	$mh = curl_multi_init();
	foreach($clist as $i => $list) {
		$no++;
		$email = $list;
		if($delim && preg_match("#".$delim."#", $list)) {
			list($email, $pwd) = explode($delim, $list);
		}
		if(empty($email)) { continue; }
		$array[$i]["no"] = $no;
		$array[$i]["list"] = $list;
		$array[$i]["email"] = $email;
		$ch[$i] = curl_init();
		curl_setopt($ch[$i], CURLOPT_URL, "https://history.paypal.com/cgi-bin/webscr?cmd=_xclick&xo_node_fallback=true&force_sa=true&upload=1&rm=2&business=".$email);
		curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch[$i], CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch[$i], CURLOPT_HEADER, 1);
		curl_setopt($ch[$i], CURLOPT_COOKIEJAR, dirname(__FILE__)."/../ppval.cook");
		curl_setopt($ch[$i], CURLOPT_COOKIEFILE, dirname(__FILE__)."/../ppval.cook");
		curl_setopt($ch[$i], CURLOPT_SSL_VERIFYPEER, 0);
    	curl_setopt($ch[$i], CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch[$i], CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch[$i], CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.81 Safari/537.36");
		curl_multi_add_handle($mh, $ch[$i]);
	}
	$active = null;
	do {
		curl_multi_exec($mh, $active);
	} while($active > 0);
	foreach($ch as $i => $c) {
		$no =  $array[$i]["no"];
		$list =  $array[$i]["list"];
		$email =  $array[$i]["email"];
		$x = curl_multi_getcontent($c);
		if(preg_match("#<html lang#", $x)) {
			if(preg_match("#<div id=\"headerSection\"><h2>#", $x)) {
				$limited++;
				file_put_contents("limited.txt", $email.PHP_EOL, FILE_APPEND);
				echo "[".date("H:i:s")." ".$no."/".$total."] ".color()["LW"]."LIMITED ".color()["WH"]." => ".$email.color()["WH"]; flush();
			}else{
				$registered++;
				file_put_contents("registered.txt", $email.PHP_EOL, FILE_APPEND);
				echo "[".date("H:i:s")." ".$no."/".$total."] ".color()["LG"]."LIVE ".color()["WH"]." => ".$email.color()["WH"]; flush();
			}
		}else{
			$die++;
			file_put_contents("die.txt", $email.PHP_EOL, FILE_APPEND);
			echo "[".date("H:i:s")." ".$no."/".$total."] ".color()["LR"]."DEAD ".color()["WH"]." => ".$email.color()["WH"]; flush();
		}
			echo " ~ PayPal eMail Validator - Powered by Kim Jisoo ❤";
		if($delpercheck) {
    		$awal = str_replace("\r", "", file_get_contents("../".$listname));
    	   	$akhir = str_replace($list."\n", "", $awal);
    	   	if($no == $total) $akhir = str_replace($list, "", $awal);
    	    file_put_contents("../".$listname, $akhir);
    	}
		echo PHP_EOL;
		curl_multi_remove_handle($mh, $c);
		usleep(7000);
	}
	curl_multi_close($mh);
}
if(empty(file_get_contents("../".$listname))) unlink("../".$listname);
echo PHP_EOL."Total: ".$total." - Registered: ".$registered." - Die: ".$die." - Limited: ".$limited.PHP_EOL."Saved to dir \"".$savetodir."\"".PHP_EOL;

function banner() {
	$out = color()["LW"]."     _____________".color()["MG"]."______________".color()["CY"]."_______________".color()["LM"]."_____________
    |                                                       |
    |           ".color()["LG"]."PayPal ".color()["CY"]."eMail ".color()["MG"]."Validator 2.0 --                |
    |      Author: ".color()["LW"]."Faiz Ainurrofiq ".color()["MG"]."(https://paisx.net/)     |
    |_____________".color()["LG"]."______________".color()["CY"]."_______________".color()["MG"]."_____________|".color()["LW"]."
                Made with a cup of ☕ and ❤ --".color()["WH"]."
".color()["WH"].PHP_EOL.PHP_EOL;
	return $out;
}
function color() {
	return array(
		"LW" => (OS == "linux" ? "\e[1;37m" : ""),
		"WH" => (OS == "linux" ? "\e[0m" : ""),
		"YL" => (OS == "linux" ? "\e[1;33m" : ""),
		"LR" => (OS == "linux" ? "\e[1;31m" : ""),
		"MG" => (OS == "linux" ? "\e[0;35m" : ""),
		"LM" => (OS == "linux" ? "\e[1;35m" : ""),
		"CY" => (OS == "linux" ? "\e[1;36m" : ""),
		"LG" => (OS == "linux" ? "\e[1;32m" : "")
	);
}
