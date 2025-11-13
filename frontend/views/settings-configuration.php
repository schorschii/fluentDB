<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

$license = new LicenseCheck($db);
$permGeneral = $cl->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_GENERAL_CONFIGURATION, false);
?>

<div class='details-header'>
	<h1><img src='img/settings.dyn.svg'><span id='page-title'><?php echo LANG('configuration_overview'); ?></span></h1>
</div>

<div class='details-abreast'>
	<div>
		<div class='controls heading'>
			<h2><?php echo LANG('license'); ?></h2>
			<div class='filler invisible'></div>
			<span><a href='https://oco.sieber.systems/licenses.php' target='_blank'><?php echo LANG('buy_license'); ?></a></span>
			<button onclick='showDialogEditSetting("license",".ocolicense",false,true,LANG["license_file"])' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
		</div>
		<table class='list'>
			<tr>
				<th><?php echo LANG('company'); ?>:</th>
				<td><?php echo htmlspecialchars($license->getCompany()); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('status'); ?>:</th>
				<td>
					<div class='alert <?php echo $license->isValid() ? 'success' : 'error'; ?>'><?php echo htmlspecialchars($license->getLicenseText()); ?></div>
					<?php echo Html::progressBar($license->getCurrentObjectCount()/$license->getObjects()*100, 'prgLicenses', 'prgLicensesText', 'fullwidth', '', $license->getCurrentObjectCount().'/'.$license->getObjects()); ?>
				</td>
			</tr>
		</table>

		<div class='controls heading'>
			<h2><?php echo LANG('configuration'); ?></h2>
			<div class='filler invisible'></div>
			<button onclick='showDialogEditGeneralConfig()' <?php if(!$permGeneral) echo 'disabled'; ?>><img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?></button>
		</div>
		<table class='list metadata'>
			<tr>
				<th><?php echo LANG('api_enabled'); ?>:</th>
				<td><?php if($db->settings->get('api-enabled')) echo LANG('yes'); else echo LANG('no'); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('api_key'); ?>:</th>
				<td><?php echo $permGeneral ? '<span class="monospace">'.htmlspecialchars($db->settings->get('api-key')).'</span>' : '<i>'.LANG('permission_denied').'</i>'; ?></td>
			</tr>
		</table>
	</div>

	<div>
		<h2><?php echo LANG('server_environment'); ?></h2>
		<table class='list'>
			<tr>
				<th><?php echo LANG('webserver_version'); ?>:</th>
				<td><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE']??'?'); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('database_server_version'); ?>:</th>
				<td><?php echo htmlspecialchars($db->getServerVersion()); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('php_version'); ?>:</th>
				<td><?php echo htmlspecialchars(phpversion()); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('post_max_size'); ?>:</th>
				<td><?php echo htmlspecialchars(ini_get('post_max_size')); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('upload_max_filesize'); ?>:</th>
				<td><?php echo htmlspecialchars(ini_get('upload_max_filesize')); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('max_input_time'); ?>:</th>
				<td><?php echo htmlspecialchars(ini_get('max_input_time')); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('max_execution_time'); ?>:</th>
				<td><?php echo htmlspecialchars(ini_get('max_execution_time')); ?></td>
			</tr>
			<tr>
				<th><?php echo LANG('memory_limit'); ?>:</th>
				<td><?php echo htmlspecialchars(ini_get('memory_limit')); ?></td>
			</tr>
		</table>

		<h2><?php echo LANG('extensions'); ?></h2>
		<?php if(count($ext->getLoadedExtensions()) == 0) { ?>
			<div class='alert info'><?php echo LANG('no_extensions_loaded'); ?></div>
		<?php } else { ?>
		<table class='list'>
			<tr>
				<th><?php echo LANG('id'); ?></th>
				<th><?php echo LANG('name'); ?></th>
				<th><?php echo LANG('version'); ?></th>
				<th><?php echo LANG('author'); ?></th>
			</tr>
			<?php foreach($ext->getLoadedExtensions() as $e) { ?>
			<tr>
				<td><?php echo htmlspecialchars($e['id']); ?></td>
				<td><?php echo htmlspecialchars($e['name']); ?></td>
				<td><?php echo htmlspecialchars($e['version']); ?></td>
				<td><?php echo htmlspecialchars($e['author']); ?></td>
			</tr>
			<?php } ?>
		</table>
		<?php } ?>
	</div>
</div>

<div class='details-abreast'>
	<?php if($permGeneral) { ?>
	<div class='stickytable'>
		<div class='controls heading'>
			<h2><?php echo LANG('all_settings'); ?></h2>
			<div class='filler invisible'></div>
			<button onclick='showDialogEditSetting()'><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('add'); ?></button>
		</div>
		<table id='tblSettingsData' class='list searchable sortable savesort actioncolumn sticky'>
			<thead>
				<tr>
					<th></th>
					<th class='searchable sortable'><?php echo LANG('key'); ?></th>
					<th class='searchable sortable'><?php echo LANG('value'); ?></th>
					<th class=''><?php echo LANG('action'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($db->selectAllSetting() as $s) { ?>
				<tr>
					<td><input type='checkbox' name='setting_id[]' value='<?php echo $s->key; ?>'></td>
					<td><?php echo htmlspecialchars($s->key); ?></td>
					<td><?php echo htmlspecialchars(shorter($s->value, 150)); ?></td>
					<td><button setting='<?php echo htmlspecialchars($s->key); ?>' onclick='showDialogEditSetting(this.getAttribute("setting"))'><img src='img/edit.dyn.svg'></button></td>
				</tr>
				<?php } ?>
			</tbody>
			<tfoot>
			<tr>
				<td colspan='999'>
					<div class='spread'>
						<div>
							<span class='counterFiltered'>0</span>/<span class='counterTotal'>0</span>&nbsp;<?php echo LANG('elements'); ?>,
							<span class='counterSelected'>0</span>&nbsp;<?php echo LANG('selected'); ?>
						</div>
						<div class='controls'>
							<button onclick='removeSelectedSetting("setting_id[]")'><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
			</tfoot>
		</table>
	</div>
	<?php } ?>
</div>
