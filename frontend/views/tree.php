<?php
$SUBVIEW = 1;
require_once('../../loader.inc.php');
require_once('../session.inc.php');
?>

<?php foreach($db->selectAllObjectTypeGroup() as $otg) { ?>
<div id='divNodeObjectTypeGroup<?php echo $otg->id; ?>' class='node expandable'>
	<a><img src='img/folder.dyn.svg'><?php echo htmlspecialchars(LANG($otg->title)); ?></a>
	<div class='subitems'>
		<?php foreach($db->selectAllObjectTypeByObjectTypeGroup($otg->id) as $ot) { ?>
		<a <?php echo Html::explorerLink('views/objects.php?id='.$ot->id); ?>>
			<?php if($ot->image) { ?><img src='<?php echo base64image($ot->image); ?>'><?php } ?>
			<?php echo htmlspecialchars(LANG($ot->title)); ?>
		</a>
		<?php } ?>
	</div>
</div>
<?php } ?>
