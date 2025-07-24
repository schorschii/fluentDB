<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('settings'); ?></span></h1>
</div>

<div class='actionmenu'>
	<a <?php echo Html::explorerLink('views/settings-configuration.php'); ?>>&rarr;&nbsp;<?php echo LANG('configuration_overview'); ?></a>
</div>
