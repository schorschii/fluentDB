<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');

// ----- prepare view -----
try {
	$object = $db->selectObject($_GET['id'] ?? -1);
	if(!$object) throw new NotFoundException();
	$objectType = $db->selectObjectType($object->object_type_id);

	// TODO
	$permissionWrite = true;
	$permissionDelete = true;

	$title = '';
	foreach($db->selectAllCategorySetByCategoryObject(1, $object->id) as $cs)
		foreach($db->selectAllCategoryValueByCategorySet($cs->id, $object->id) as $cv)
			if($cv->constant == 'title') $title = $cv->value;
} catch(NotFoundException $e) {
	die("<div class='alert warning'>".LANG('not_found')."</div>");
} catch(PermissionException $e) {
	die("<div class='alert warning'>".LANG('permission_denied')."</div>");
} catch(InvalidRequestException $e) {
	die("<div class='alert error'>".$e->getMessage()."</div>");
}
?>

<div class='details-header'>
	<h1>
		<?php if($objectType->image) { ?><img src='<?php echo base64image($objectType->image); ?>'><?php } ?>
		<span id='page-title'><?php echo htmlspecialchars($title); ?></span>
	</h1>
</div>

<?php foreach($db->selectAllCategoryByObjectType($object->object_type_id) as $c) { ?>
	<div class='details-abreast'>
		<div>
			<h2 id='hCategory<?php echo $c->id; ?>'><?php echo htmlspecialchars(LANG($c->title)); ?></h2>
		</div>
		<div>
			<?php if($c->multivalue) { ?>
				<div class='controls'>
					<button onclick='editMode(obj("divCategory<?php echo $c->id; ?>"))' <?php if(!$permissionWrite) echo 'disabled'; ?>><img src='img/add.dyn.svg'>&nbsp;<?php echo LANG('new'); ?></button>
				</div>
			<?php } ?>
		</div>
	</div>

	<?php
	$sets = $db->selectAllCategorySetByCategoryObject($c->id, $object->id);
	if(empty($sets) || $c->multivalue) {
		array_splice($sets, 0, 0, [Models\ObjCategorySet::initWithValues(-1)]);
	}
	?>

	<?php foreach($sets as $cs) { ?>
		<div id='divCategory<?php echo $c->id; ?>' class='details-abreast category <?php if($cs->id<0 && $c->multivalue) echo 'template'; ?>' set='<?php echo $cs->id; ?>'>
			<div>
				<table class='list form metadata category'>
					<?php foreach($cs->id<0 ? $db->selectAllCategoryValueByCategory($c->id, $object->id) : $db->selectAllCategoryValueByCategorySet($cs->id) as $cv) { ?>
					<tr>
						<th><?php echo htmlspecialchars(LANG($cv->title)); ?></th>
						<td class='dualInput'>
							<div class='<?php if($cv->ro) echo 'ro-label'; else echo 'label'; ?> <?php if($cv->type=='separator') echo 'separator'; ?>'>
								<?php
								$selectedValue = null;
								if($cv->type == 'dialog' || $cv->type == 'dialog_plus' || $cv->type == 'multiselect') {
									if(empty($cv->linked_dialog_value_title)) echo '&nbsp;';
									else {
										$selectedValue = $cv->linked_dialog_value_id;
										echo htmlspecialchars(LANG($cv->linked_dialog_value_title));
									}
								} elseif(explode(':',$cv->type)[0] == 'object') {
									if(empty($cv->linked_object_title)) echo '&nbsp;';
									else {
										$selectedValue = $cv->linked_object_id;
										echo '<a '.Html::explorerLink('views/object.php?id='.intval($cv->linked_object_id)).'>'.htmlspecialchars($cv->linked_object_title).'</a>';
									}
								} else {
									echo empty($cv->value) ? '&nbsp;' : nl2br(htmlspecialchars($cv->value));
								} ?>
							</div>
							<?php if(!$cv->ro) { ?>

							<?php
							$inputName = $cv->category_id.':'.$cs->id.':'.$cv->category_field_id;
							if($cv->type == 'text') {
							?>
								<input type='text' name='<?php echo $inputName; ?>' value='<?php echo htmlspecialchars($cv->value,ENT_QUOTES); ?>' />
							<?php
							} elseif($cv->type == 'text-multiline') {
							?>
								<textarea name='<?php echo $inputName; ?>'><?php echo htmlspecialchars($cv->value); ?></textarea>
							<?php
							} elseif($cv->type == 'datetime') {
							?>
								<input type='hidden' name='<?php echo $inputName; ?>' value='<?php echo htmlspecialchars($cv->value,ENT_QUOTES); ?>' />
								<input type='date' value='<?php echo htmlspecialchars(explode(' ',$cv->value)[0],ENT_QUOTES); ?>' oninput='this.parentElement.querySelectorAll("input[type=hidden]")[0].value = this.value+" "+this.parentElement.querySelectorAll("input[type=time]")[0].value' />
								<input type='time' value='<?php echo htmlspecialchars(explode(' ',$cv->value)[1]??'',ENT_QUOTES); ?>' oninput='this.parentElement.querySelectorAll("input[type=hidden]")[0].value = this.parentElement.querySelectorAll("input[type=date]")[0].value+" "+this.value' />
							<?php
							} elseif($cv->type == 'date') {
							?>
								<input type='date' name='<?php echo $inputName; ?>' value='<?php echo htmlspecialchars($cv->value,ENT_QUOTES); ?>' />
							<?php
							} elseif($cv->type == 'time') {
							?>
								<input type='time' name='<?php echo $inputName; ?>' value='<?php echo htmlspecialchars($cv->value,ENT_QUOTES); ?>' />
							<?php
							} elseif($cv->type == 'dialog' || $cv->type == 'dialog_plus' || $cv->type == 'multiselect') {
							?>
								<select name='<?php echo $inputName; ?>'>
									<?php foreach($db->selectAllDialogValueByCategoryField($cv->category_field_id) as $value) { ?>
										<option value='<?php echo $value->id; ?>' <?php if($selectedValue==$value->id) echo'selected'; ?>><?php echo htmlspecialchars(LANG($value->title)); ?></option>
									<?php } ?>
								</select>
							<?php
							} elseif(explode(':',$cv->type)[0] == 'object') {
							?>
								<select name='<?php echo $inputName; ?>'>
									<?php foreach($db->selectAllObjectByObjectType(explode(':',$cv->type)[1]??-1) as $o) { ?>
										<option value='<?php echo $o->id; ?>' <?php if($selectedValue==$o->id) echo'selected'; ?>><?php echo htmlspecialchars($o->title); ?></option>
									<?php } ?>
								</select>
							<?php } ?>

							<?php } ?>
						</td>
					</tr>
					<?php } ?>
			</table>
			</div>
			<div>
				<button class='save primary hidden' onclick='saveCategory(<?php echo $object->id; ?>, this.parentNode.parentNode, hCategory<?php echo $c->id; ?>.innerText)'>
					<img src='img/tick.white.svg'>&nbsp;<?php echo LANG('save'); ?>
				</button>
				<button class='cancel hidden' onclick='viewMode(this.parentNode.parentNode)'>
					<img src='img/close.dyn.svg'>&nbsp;<?php echo LANG('cancel'); ?>
				</button>
				<!-- -->
				<button class='edit' onclick='editMode(this.parentNode.parentNode)'
					<?php if(!$permissionWrite) echo 'disabled'; ?>>
					<img src='img/edit.dyn.svg'>&nbsp;<?php echo LANG('edit'); ?>
				</button>
				<button class='clear <?php if($c->id == CoreLogic::GENERAL_CATEGORY_ID || $cs->id <= 0) echo 'invisible'; ?>'
					onclick='confirmDeleteCategorySet([<?php echo $cs->id; ?>], hCategory<?php echo $c->id; ?>.innerText)'
					<?php if(!$permissionDelete) echo 'disabled'; ?>>
					<img src='img/delete.dyn.svg'>&nbsp;<?php echo LANG('delete'); ?>
				</button>
			</div>
		</div>
	<?php } ?>

<?php } ?>
