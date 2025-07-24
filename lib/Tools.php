<?php

function LANG($key) {
	return LanguageController::getMessageFromSingleton($key);
}

function startsWith( $haystack, $needle ) {
	$length = strlen( $needle );
	return substr( $haystack, 0, $length ) === $needle;
}

function isIE() {
	return preg_match('~MSIE|Internet Explorer~i', $_SERVER['HTTP_USER_AGENT'])
		|| (strpos($_SERVER['HTTP_USER_AGENT'], 'Trident/7.0; rv:11.0') !== false)
		|| strpos($_SERVER['HTTP_USER_AGENT'], 'Edge');
}

function prettyJson($json) {
	$parsed = json_decode($json);
	if($parsed === null) return $json;
	return json_encode($parsed, JSON_PRETTY_PRINT);
}

function niceSize($value, $useBinary=true, $round=1, $echoZeroIfEmpty=false) {
	if($value === 0 || ($echoZeroIfEmpty && empty($value))) return "0 B";
	if(empty($value)) return "";
	if($useBinary) {
		if($value < 1024) return $value . " B";
		else if($value < 1024*1024) return round($value / 1024, $round) . " KiB";
		else if($value < 1024*1024*1024) return round($value / 1024 / 1024, $round) . " MiB";
		else if($value < 1024*1024*1024*1024) return round($value / 1024 / 1024 /1024, $round) . " GiB";
		else return round($value / 1024 / 1024 / 1024 / 1024, $round) . " TiB";
	} else {
		if($value < 1000) return $value . " B";
		else if($value < 1000*1000) return round($value / 1000, $round) . " KB";
		else if($value < 1000*1000*1000) return round($value / 1000 / 1000, $round) . " MB";
		else if($value < 1000*1000*1000*1000) return round($value / 1000 / 1000 / 1000, $round) . " GB";
		else return round($value / 1000 / 1000 / 1000 / 1000, $round) . " TB";
	}
}

function niceTime($seconds) {
	if($seconds < 60)
		return sprintf('%d '.LANG('seconds'), $seconds);
	elseif($seconds < 60*60*24)
		return sprintf('%d '.LANG('hours').', %d '.LANG('minutes'), ($seconds/3600), ($seconds/60%60));
	else return round($seconds/60/60/24).' '.LANG('days');
}

function randomString($length = 30) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
	$charactersLength = strlen($characters);
	$randomString = '';
	for($i = 0; $i < $length; $i++) {
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

function shorter($text, $charsLimit=40, $dots=true) {
	if(strlen($text) > $charsLimit) {
		$new_text = substr($text, 0, $charsLimit);
		$new_text = trim($new_text);
		return $new_text . ($dots ? "..." : "");
	} else {
		return $text;
	}
}

function localTimeToUtc($dateTimeString) {
	$date = new DateTime($dateTimeString, new DateTimeZone(date_default_timezone_get()));
	$date->setTimezone(new DateTimeZone('UTC'));
	return $date->format('Y-m-d H:i:s');
}
function utcTimeToLocal($utcTimeString) {
	$date = new DateTime($utcTimeString, new DateTimeZone('UTC'));
	$date->setTimezone(new DateTimeZone(date_default_timezone_get()));
	return $date->format('Y-m-d H:i:s');
}

// converts IPv4 or v6 address to string with bits
function ipAddressToBits($inet) {
	$inet = inet_pton($inet);
	if($inet === false) return false;
	$splitted = str_split($inet);
	$binaryip = '';
	foreach($splitted as $char) {
		$binaryip .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
	}
	return $binaryip;
}
function isIpInRange($ip, $range) {
	// $range is in IP/CIDR format e.g. 127.0.0.1/24 or CAFF:EECA:FFEE:0000::/64
	if(strpos( $range, '/' ) == false) $range .= '/32'; // fallback range
	list( $range, $maskBits ) = explode( '/', $range, 2 );
	$binaryNet = ipAddressToBits($range);
	$binaryIp  = ipAddressToBits($ip);
	$ipNetBits = substr($binaryIp, 0, $maskBits);
	$netBits   = substr($binaryNet, 0, $maskBits);
	if($binaryIp === false || $binaryNet === false) {
		throw new Exception(LANG('invalid_ip_address'));
	}
	return ($ipNetBits === $netBits);
}

function base64image($data) {
	file_put_contents('/tmp/img', $data);
	$type = mime_content_type('/tmp/img');
	return 'data:'.$type.';base64,'.base64_encode($data);
}
