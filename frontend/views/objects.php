<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<?php
if(!empty($_GET['id'])) {
try {
	$ot = $db->selectObjectType($_GET['id']);
	if(!$ot) throw new NotFoundException();
	$objects = $db->selectAllObjectByObjectType($ot->id);
	$fields = $db->selectAllListViewFieldByObjectTypeSystemUser($ot->id, $currentSystemUser->id);

	// TODO
	$permissionCreate = true;
	$permissionDelete = true;
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<h1>
	<?php if($ot->image) { ?><img src='<?php echo base64image($ot->image); ?>'><?php } ?>
	<span id='page-title'><?php echo htmlspecialchars(LANG($ot->title)); ?></span>
</h1>
<div class='controls'>
	<button onclick='createObject(<?php echo $ot->id; ?>)' <?php if(!$permissionCreate) echo 'disabled'; ?>>
		<img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new'); ?>
	</button>
</div>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblObjectData<?php echo $ot->id; ?>' class='list searchable sortable savesort objects'>
		<thead>
			<tr>
				<th><input type='checkbox' class='toggleAllChecked'></th>
				<?php foreach($fields as $field) { ?>
					<th class='searchable sortable'><?php echo LANG($field->title); ?></th>
				<?php } ?>
			</tr>
		</thead>
		<tbody>
		<?php
		$ids = [];
		foreach($objects as $object) $ids[] = $object->id;
		$objectFieldValues = $db->selectAllCategoryFieldValueByObject($ids, $fields);
		foreach($objectFieldValues as $ofv) {
			echo "<tr>";
			$counter = 0;
			foreach($ofv as $value) {
				if($counter == 0) {
					echo "<td><input type='checkbox' name='object_id[]' value='".intval($ofv[0])."'></td>";
				} else {
					if($counter == 1 && empty($value)) $value = htmlspecialchars(LANG('empty_placeholder'));
					elseif(empty($value)) $value = '&nbsp;';
					else $value = nl2br(htmlspecialchars($value));
					echo "<td><a ".Html::explorerLink('views/object.php?id='.intval($ofv[0]))." class='".($counter>1 ? 'nocolor' : '')."'>".$value."</td>";
				}
				$counter ++;
			}
			echo "</tr>";
		}
		?>
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
							<button class='downloadCsv'><img src='img/csv.dyn.svg'>&nbsp;<?php echo LANG('csv'); ?></button>
							<button onclick='confirmRemoveObjects(getSelectedCheckBoxValues("object_id[]"))' <?php if(!$permissionDelete) echo 'disabled'; ?>><img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?></button>
						</div>
					</div>
				</td>
			</tr>
		</tfoot>
		</table>
	</div>
</div>

<?php } elseif(!empty($_GET['group_id'])) {
try {
	$otg = $db->selectObjectTypeGroup($_GET['group_id']);
	if(!$otg) throw new NotFoundException();
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<h1>
	<img src='img/folder.dyn.svg'><span id='page-title'><?php echo htmlspecialchars(LANG($otg->title)); ?></span>
</h1>

<div class='controls subfolders'>
<?php foreach($db->selectAllObjectTypeByObjectTypeGroup($_GET['group_id']) as $ot) { ?>
	<a class='box' <?php echo Html::explorerLink('views/objects.php?id='.$ot->id); ?>>
		<?php if($ot->image) { ?><img src='<?php echo base64image($ot->image); ?>'><?php } ?>
		<?php echo htmlspecialchars(LANG($ot->title)); ?>
	</a>
<?php } ?>
</div>

<?php } ?>