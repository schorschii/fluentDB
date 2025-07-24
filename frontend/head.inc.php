<link rel='icon' href='img/logo.dyn.svg' type='image/svg'>
<link rel='apple-touch-icon' sizes='1024x1024' href='img/logo.png'>

<link rel='stylesheet' type='text/css' href='css/main.css?v=<?php echo urlencode(FluentDbServer::APP_VERSION); ?>'>

<script src='js/strings.js.php?v=<?php echo urlencode(FluentDbServer::APP_VERSION); ?>'></script>
<script src='js/main.js?v=<?php echo urlencode(FluentDbServer::APP_VERSION); ?>'></script>
<script src='js/table.js?v=<?php echo urlencode(FluentDbServer::APP_VERSION); ?>'></script>
<script src='js/notification.js?v=<?php echo urlencode(FluentDbServer::APP_VERSION); ?>'></script>
<script src='js/confetti.js?v=<?php echo urlencode(FluentDbServer::APP_VERSION); ?>'></script>

<?php
// include extension JS
foreach($ext->getLoadedExtensions() as $e) {
	if(!isset($e['frontend-js']) || !is_array($e['frontend-js'])) continue;
	foreach($e['frontend-js'] as $filename => $path) {
		echo '<script src="js/'.htmlspecialchars(basename($filename), ENT_QUOTES).'?v='.urlencode($e['version']).'"></script>'."\n";
	}
}
// include extension CSS
foreach($ext->getLoadedExtensions() as $e) {
	if(!isset($e['frontend-css']) || !is_array($e['frontend-css'])) continue;
	foreach($e['frontend-css'] as $filename => $path) {
		echo '<link rel="stylesheet" type="text/css" href="css/'.htmlspecialchars(basename($filename), ENT_QUOTES).'?v='.urlencode($e['version']).'" />'."\n";
	}
}
?>

<link rel='prefetch' as='image' href='img/expand.dyn.svg'>
<link rel='prefetch' as='image' href='img/collapse.dyn.svg'>
<link rel='prefetch' as='image' href='img/close.opacity.svg'>
<link rel='prefetch' as='image' href='img/info.message.svg'>
<link rel='prefetch' as='image' href='img/success.message.svg'>
<link rel='prefetch' as='image' href='img/warning.message.svg'>
<link rel='prefetch' as='image' href='img/error.message.svg'>

<meta name='viewport' content='width=device-width'>
<meta name='author' content='Georg Sieber'>
