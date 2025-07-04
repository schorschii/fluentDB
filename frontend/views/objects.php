<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

try {
	$ot = $db->selectObjectType($_GET['id'] ?? -1);
	if(!$ot) throw new NotFoundException();
	$objects = $db->selectAllObjectByObjectType($_GET['id'] ?? -1);
	$fields = $db->selectAllListViewFieldByObjectTypeSystemUser($_GET['id'] ?? -1, $currentSystemUser->id);

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

<h1><?php if($ot->image) { ?><img src='<?php echo base64image($ot->image); ?>'><?php } ?><span id='page-title'><?php echo htmlspecialchars($ot->title); ?></span></h1>
<div class='controls'>
	<button onclick='createObject(<?php echo $ot->id; ?>)' <?php if(!$permissionCreate) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new'); ?></button>
</div>

<div class='details-abreast'>
	<div class='stickytable'>
		<table id='tblObjectData' class='list searchable sortable savesort objects'>
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
					echo "<td><a ".explorerLink('views/object.php?id='.intval($ofv[0])).">".$value."</td>";
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
