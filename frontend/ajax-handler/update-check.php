<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

function checkUpdate() {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, FluentDbServer::UPDATE_API_URL);
	curl_setopt($ch, CURLOPT_USERAGENT, 'OCO-Server '.FluentDbServer::APP_VERSION.' '.FluentDbServer::APP_RELEASE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 2);
	$output = curl_exec($ch);
	curl_close($ch);  
	$json = json_decode($output, true);
	if(!$json) return false;
	foreach($json as $item) {
		if(empty($item['tag_name'])) continue;
		$availVersion = ltrim($item['tag_name'], 'v');
		if(version_compare(FluentDbServer::APP_VERSION, $availVersion, '<'))
			return $availVersion.(empty($item['prerelease']) ? '' : ' '.LANG('prerelease_note'));
	}
	return false;
}

$updateResult = checkUpdate();
if($updateResult) die($updateResult);
