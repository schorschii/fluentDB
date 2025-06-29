<?php

class CoreLogic {

	/*
		 Class CoreLogic
		 Database Abstraction Layer Wrapper

		 Adds additional (permission) checks & logic before the database is accessed and sanitizes user input.
		 It's public functions are used by the web frontend and the client API.

		Function naming (intentionally different from DatabaseController to easily distinguish if we operate directly with the database or with (permission) checks):
		 - prefix oriented on frontend (JS) command: get, create, edit, remove, add (for group memberships)
		 - entity name singular for single objects (e.g. "Computer") or plural if an array is returned (e.g. "Computers")
	*/

	protected /*DatabaseController*/ $db;
	protected /*Models\SystemUser*/ $su;
	protected /*PermissionManager*/ $pm;

	function __construct($db, $systemUser=null) {
		$this->db = $db;
		$this->su = $systemUser;
	}

	/*** Permission Check Logic ***/
	public function checkPermission($ressource, String $method, Bool $throw=true, $ressourceParentGroups=null) {
		// do not check permissions if CoreLogic is used in system context (e.g. cron jobs)
		if($this->su !== null && $this->su instanceof Models\SystemUser) {
			if($this->pm === null) $this->pm = new \PermissionManager($this->db, $this->su);
			$checkResult = $this->pm->hasPermission($ressource, $method, $ressourceParentGroups);
			if(!$checkResult && $throw) throw new \PermissionException();
			return $checkResult;
		} else {
			return true;
		}
	}
	public function getPermissionEntry($ressource, String $method) {
		if($this->pm === null) $this->pm = new \PermissionManager($this->db, $this->su);
		return $this->pm->getPermissionEntry($ressource, $method);
	}

	/*** Object Operations ***/
	public function getObjects(Object $filterRessource=null) {
		if($filterRessource === null) {
			$objectsFiltered = [];
			foreach($this->db->selectAllComputer() as $computer) {
				if($this->checkPermission($computer, PermissionManager::METHOD_READ, false))
					$objectsFiltered[] = $computer;
			}
			return $objectsFiltered;
		} else {
			throw new InvalidArgumentException('Filter for this ressource type is not implemented');
		}
	}
	public function getObject($id) {
		$object = $this->db->selectComputer($id);
		if(empty($object)) throw new NotFoundException();
		$this->checkPermission($object, PermissionManager::METHOD_READ);
		return $object;
	}
	public function createObject($title) {
		$this->checkPermission(new Models\Computer(), PermissionManager::METHOD_CREATE);

		$finalTitle = trim($title);
		if(empty($finalTitle)) {
			throw new InvalidRequestException(LANG('title_cannot_be_empty'));
		}
		$insertId = $this->db->insertObject($finalTitle);
		if(!$insertId) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'fluentdb.object.create', ['title'=>$finalTitle]);
		return $insertId;
	}
	public function removeObject($id) {
		$object = $this->db->selectObject($id);
		if(empty($object)) throw new NotFoundException();
		$this->checkPermission($object, PermissionManager::METHOD_DELETE);

		$result = $this->db->deleteComputer($object->id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $object->id, 'fluentdb.object.delete', json_encode($object));
		return $result;
	}
	public function updateCategories(int $objId, array $editFields, bool $recurse=false) {
		$createdSets = [];
		foreach($editFields as $value) {
			// TODO transaction
			// TODO permission check
			// TODO logbook changes
			$category = $this->db->selectCategory($value['category']);
			$sets = $this->db->selectAllCategorySetByCategoryObject($value['category'], $objId);
			if($category->multivalue == 0 && count($sets)) {
				$this->db->replaceObjectCategoryValue($sets[0]->id, $value['field'], $value['value']);
			} else {
				if($value['set'] < 0 && array_key_exists($value['category'], $createdSets)) {
					$setId = $createdSets[$value['category']];
				} elseif($value['set'] < 0 && !array_key_exists($value['category'], $createdSets)) {
					$setId = $this->db->insertObjectCategorySet($objId, $value['category']);
					$createdSets[$value['category']] = $setId;
				} else {
					$setId = $value['set'];
				}
				$this->db->replaceObjectCategoryValue($setId, $value['field'], $value['value']);
			}
		}
		if(!$recurse) $this->updateCategories($objId, [
			['category'=>1, 'field'=>11, 'value'=>date('Y-m-d H:i:s')]
		], true);
	}

}
