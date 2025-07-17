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
	protected /*Models\Obj*/ $su;
	protected /*PermissionManager*/ $pm;

	const GENERAL_CATEGORY_ID  = 1;
	const TITLE_FIELD_ID       = 1;
	const CREATED_FIELD_ID     = 9;
	const CREATED_BY_FIELD_ID  = 10;
	const CHANGED_FIELD_ID     = 11;
	const CHANGED_BY_FIELD_ID  = 12;

	const OBJTYPE_PERSON_ID          = 1;
	const LOGIN_CATEGORY_ID          = 2;
	const DISABLED_LOGIN_FIELD_ID    = 15;
	const USERNAME_FIELD_ID          = 16;
	const PASSWORD_FIELD_ID          = 17;
	const UNIQUE_IDENTIFIER_FIELD_ID = 18;
	const LAST_LOGIN_FIELD_ID        = 19;

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
	public function createObject($typeId) {
		#$this->checkPermission(new Models\Obj(), PermissionManager::METHOD_CREATE); // TODO

		$insertId = $this->db->insertObject($typeId);
		if(!$insertId) throw new Exception(LANG('unknown_error'));

		$this->updateCategories($insertId, [
			new Models\UpdateField(self::GENERAL_CATEGORY_ID, self::CREATED_FIELD_ID, -1, date('Y-m-d H:i:s')),
			new Models\UpdateField(self::GENERAL_CATEGORY_ID, self::CREATED_BY_FIELD_ID, -1, $this->su->username),
		]);

		#$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $insertId, 'fluentdb.object.create', ['title'=>$finalTitle]);
		return $insertId;
	}
	public function removeObject($id) {
		$object = $this->db->selectObject($id);
		if(empty($object)) throw new NotFoundException();
		#$this->checkPermission($object, PermissionManager::METHOD_DELETE);

		$result = $this->db->deleteObject($object->id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		#$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $object->id, 'fluentdb.object.delete', json_encode($object));
		return $result;
	}
	public function removeCategorySet($id) {
		$cs = $this->db->selectCategorySet($id);
		if(empty($cs)) throw new NotFoundException();
		$object = $this->db->selectObject($cs->object_id);
		if(empty($object)) throw new NotFoundException();
		$category = $this->db->selectCategory($cs->category_id);
		if(empty($category)) throw new NotFoundException();
		#$this->checkPermission($object, PermissionManager::METHOD_EDIT);
		#$this->checkPermission($category, PermissionManager::METHOD_EDIT);

		if($cs->category_id == self::GENERAL_CATEGORY_ID)
			throw new Exception('Refused to delete the general category');

		$result = $this->db->deleteCategorySet($id);
		if(!$result) throw new Exception(LANG('unknown_error'));
		#$this->db->insertLogEntry(Models\Log::LEVEL_INFO, $this->su->username, $cs->id, 'fluentdb.category.delete', json_encode($cs));
		return $result;
	}
	public function updateCategories(int $objId, array $editFields, bool $recurse=false) {
		$createdSets = [];
		if(!$recurse) $this->db->getDbHandle()->beginTransaction();
		foreach($editFields as $field) {
			// TODO permission check
			// TODO logbook changes
			$category = $this->db->selectCategory($field->category_id);
			$sets = $this->db->selectAllCategorySetByCategoryObject($field->category_id, $objId);
			if($category->multivalue == 0 && count($sets)) {
				$this->db->replaceObjectCategoryValue($sets[0]->id, $field->category_field_id, $field->value);
			} else {
				if($field->category_set_id < 0 && array_key_exists($field->category_id, $createdSets)) {
					$setId = $createdSets[$field->category_id];
				} elseif($field->category_set_id < 0 && !array_key_exists($field->category_id, $createdSets)) {
					$setId = $this->db->insertObjectCategorySet($objId, $field->category_id);
					$createdSets[$field->category_id] = $setId;
				} else {
					$setId = $field->category_set_id;
				}
				$this->db->replaceObjectCategoryValue($setId, $field->category_field_id, $field->value);
			}
		}
		if(!$recurse) $this->updateCategories($objId, [
			new Models\UpdateField(self::GENERAL_CATEGORY_ID, self::CHANGED_FIELD_ID, -1, date('Y-m-d H:i:s')),
			new Models\UpdateField(self::GENERAL_CATEGORY_ID, self::CHANGED_BY_FIELD_ID, -1, $this->su->username),
		], true);
		if(!$recurse) $this->db->getDbHandle()->commit();
	}

}
