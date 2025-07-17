<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

// ----- prepare view -----
$stats = $db->getStats();
$sysload = sys_getloadavg()[2];
$license = new LicenseCheck($db);
?>

<div id='homepage'>
	<img src='img/logo.dyn.svg'>
	<p>
		<div class='title'><?php echo LANG('project_name'); ?></div>
		<div class='subtitle'><?php echo LANG('project_subtitle'); ?></div>
	</p>

	<?php if(!$license->isValid()) { ?>
		<div class='alert bold error'><?php echo LANG('your_license_is_invalid'); ?></div>
	<?php } elseif(!$license->isFree() && $license->getRemainingTime() < 60*60*24*14) {
		$remainingDays = round($license->getRemainingTime() / (60*60*24));
	?>
		<div class='alert bold warning'><?php echo str_replace('%1', $remainingDays, LANG('your_license_expires_in_days')); ?></div>
	<?php } ?>

	<div class='box fullwidth margintop stats'>
		<div class='bars'>
			<div class=' version'>
				<?php echo LANG('version').' '.FluentDbServer::APP_VERSION.' '.FluentDbServer::APP_RELEASE; ?>
			</div>
		</div>
		<hr/>
		<div>
			<div><?php echo $stats['objects'].' '.LANG('objects'); ?></div>
			<div><?php echo $stats['object_types'].' '.LANG('object_types'); ?></div>
		</div>
		<hr/>
		<div>
			<div class='motd'><?php echo LANG($db->settings->get('motd')); ?></div>
		</div>
		<hr/>
		<div>
			<div>
				<img src='img/itinventory.png' style='max-height:120px'>
			</div>
			<div>
				<h3>itInventory - CMDB Scan-App</h3>
				<div>
					<a href='https://play.google.com/store/apps/details?id=systems.sieber.itinventory' target='_blank'><img src='img/playstore-badge.svg' style='max-height:50px'></a>
					<a href='https://apps.apple.com/de/app/itinventory-i-doit-scanner/id1442661035' target='_blank'><img src='img/appstore-badge.svg' style='max-height:50px'></a>
				</div>
			</div>
		</div>
	</div>

	<div class='footer'>
		<?php require('partial/copyright.php'); ?>
	</div>
</div>
