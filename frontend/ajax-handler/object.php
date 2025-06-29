<?php
$SUBVIEW = 1;
require_once(__DIR__.'/../../loader.inc.php');
require_once(__DIR__.'/../session.inc.php');

try {

	if(!empty($_POST['create_object_of_type'])) {
		die($db->insertObject($_POST['create_object_of_type']));
	}

	if(!empty($_POST['edit_id'])) {
		$updates = [];
		foreach($_POST as $key => $value) {
			$splitter = explode(':', $key);
			if(count($splitter) != 3) continue;
			$updates[] = [
				'category' => $splitter[0],
				'set' => $splitter[1],
				'field' => $splitter[2],
				'value' => $value,
			];
		}
		$cl->updateCategories(intval($_POST['edit_id']), $updates);
		die();
	}

	if(!empty($_POST['remove_category_set_id']) && is_array($_POST['remove_category_set_id'])) {
		foreach($_POST['remove_category_set_id'] as $id) {
			$db->deleteCategorySet($id);
		}
		die();
	}

	if(!empty($_POST['remove_id']) && is_array($_POST['remove_id'])) {
		foreach($_POST['remove_id'] as $id) {
			$db->deleteObject($id);
		}
		die();
	}

} catch(PermissionException $e) {
	header('HTTP/1.1 403 Forbidden');
	die(LANG('permission_denied'));
} catch(Exception $e) {
	header('HTTP/1.1 400 Invalid Request');
	die($e->getMessage());
}

header('HTTP/1.1 400 Invalid Request');
die(LANG('unknown_method'));
