<?php

class DatabaseController {

	/*
		 Class DatabaseController
		 Database Abstraction Layer

		 Handles direct database access.

		 Function naming:
		 - prefix oriented on SQL command: select, insert, update, insertOrUpdate, delete, search (special for search operations)
		 - if it returns an array, the word "All" is inserted
		 - entity name singular (e.g. "Object")
		 - "By<Attribute>" suffix if objects are filtered by attributes other than the own object id (e.g. "ByObjectType")
	*/

	protected $dbh;
	private $stmt;

	public $settings;

	function __construct() {
		try {
			$this->dbh = new PDO(
				DB_TYPE.':host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';',
				DB_USER, DB_PASS,
				array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4')
			);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->settings = new SettingsController($this);
		} catch(Exception $e) {
			error_log($e->getMessage());
			throw new Exception('Failed to establish database connection to ›'.DB_HOST.'‹. Gentle panic.');
		}
	}

	private static function compileSqlInValues($array) {
		if(empty($array)) $array = [-1];
		$in_placeholders = ''; $in_params = []; $i = 0;
		foreach($array as $item) {
			$key = ':id'.$i++;
			$in_placeholders .= ($in_placeholders ? ',' : '').$key; // :id0,:id1,:id2
			$in_params[$key] = $item; // collecting values into a key-value array
		}
		return [$in_placeholders, $in_params];
	}

	// Database Handle Access for Extensions
	public function getDbHandle() {
		return $this->dbh;
	}
	public function getLastStatement() {
		return $this->stmt;
	}

	// Special Queries
	public function getServerVersion() {
		return $this->dbh->getAttribute(PDO::ATTR_SERVER_VERSION);
	}
	public function existsSchema() {
		$this->stmt = $this->dbh->prepare('SHOW TABLES LIKE "object"');
		$this->stmt->execute();
		if($this->stmt->rowCount() != 1) return false;
		$this->stmt = $this->dbh->prepare('SHOW TABLES LIKE "system_user"');
		$this->stmt->execute();
		return ($this->stmt->rowCount() == 1);
	}
	public function isEstablished() {
		$this->stmt = $this->dbh->prepare('SELECT * FROM system_user');
		$this->stmt->execute();
		return ($this->stmt->rowCount() > 0);
	}
	public function getStats() {
		$this->stmt = $this->dbh->prepare(
			'SELECT
			(SELECT count(id) FROM object) AS "objects",
			(SELECT count(id) FROM object_type) AS "object_types"
			FROM DUAL'
		);
		$this->stmt->execute();
		foreach($this->stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
			return $row;
		}
	}

	// Object Operations
	public function searchAllObject($search, $limit=null) {
		$this->stmt = $this->dbh->prepare(
			'SELECT ot.title AS "object_type_title", o.*, ocv.value,
			(SELECT `value` FROM `object_category_value` ocv2 INNER JOIN `object_category_set` ocs2 ON ocs2.id = ocv2.object_category_set_id WHERE ocs2.object_id = o.id AND ocv2.category_field_id = 1 LIMIT 1) AS "title"
			FROM `object_category_value` ocv
			INNER JOIN `object_category_set` ocs ON ocs.id = ocv.object_category_set_id
			INNER JOIN `object` o ON o.id = ocs.object_id
			INNER JOIN `object_type` ot ON ot.id = o.object_type_id
			WHERE `value` LIKE :search
			GROUP BY o.id'
			.($limit==null ? '' : 'LIMIT '.intval($limit))
		);
		$this->stmt->execute([':search' => '%'.$search.'%']);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Obj');
	}
	public function selectAllObjectTypeGroup() {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM `object_type_group`'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ObjTypeGroup');
	}
	public function selectAllObjectTypeByObjectTypeGroup($object_type_group_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM `object_type`'
			.($object_type_group_id === null ? '' : ' WHERE object_type_group_id = :object_type_group_id')
		);
		if($object_type_group_id === null)
			$this->stmt->execute();
		else
			$this->stmt->execute(['object_type_group_id' => $object_type_group_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ObjType');
	}
	public function selectObjectType($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM `object_type` WHERE id = :id'
		);
		$this->stmt->execute(['id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ObjType') as $row) {
			return $row;
		}
	}
	public function selectAllObjectByObjectType($object_type_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM `object` WHERE object_type_id = :object_type_id'
		);
		$this->stmt->execute(['object_type_id' => $object_type_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Obj');
	}
	public function selectObject($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM `object` WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Obj') as $row) {
			return $row;
		}
	}
	public function selectCategory($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM `category` WHERE id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Obj') as $row) {
			return $row;
		}
	}
	public function selectCategoryByConstant($constant) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM `category` WHERE constant = :constant'
		);
		$this->stmt->execute([':constant' => $constant]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Obj') as $row) {
			return $row;
		}
	}
	public function selectCategoryFieldByCategoryConstant($category_id, $constant) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM `category_field` WHERE category_id = :category_id AND constant = :constant'
		);
		$this->stmt->execute([':category_id' => $category_id, ':constant' => $constant]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Obj') as $row) {
			return $row;
		}
	}
	public function selectAllCategoryByObjectType($object_type_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT c.* FROM `category` c
			INNER JOIN object_type_category otc ON otc.category_id = c.id
			WHERE otc.object_type_id = :object_type_id
			ORDER BY otc.order'
		);
		$this->stmt->execute([':object_type_id' => $object_type_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Obj');
	}
	public function selectAllCategoryFieldByCategory($category_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cf.* FROM `category_field` cf
			WHERE cf.category_id = :category_id
			ORDER BY cf.order'
		);
		$this->stmt->execute([':category_id' => $category_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Obj');
	}
	public function selectAllCategorySetByCategoryObject($category_id, $object_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM `object_category_set` WHERE category_id = :category_id AND object_id = :object_id'
		);
		$this->stmt->execute([':category_id' => $category_id, ':object_id' => $object_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Obj');
	}
	public function selectAllCategoryValueByCategorySet($object_category_set_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cf.category_id, cf.id AS "category_field_id", cf.constant, cf.title, cf.type, cf.ro, ocv.value
			FROM `category_field` cf
			LEFT JOIN object_category_value ocv ON cf.id = ocv.category_field_id AND ocv.object_category_set_id = :object_category_set_id
			WHERE cf.category_id = (SELECT category_id FROM object_category_set WHERE id = :object_category_set_id)
			AND (ocv.object_category_set_id = :object_category_set_id OR ocv.object_category_set_id IS NULL)
			ORDER BY cf.order'
		);
		$this->stmt->execute([':object_category_set_id' => $object_category_set_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Obj');
	}
	public function selectAllCategoryValueByCategory($category_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT cf.category_id, cf.id AS "category_field_id", cf.constant, cf.title, cf.type, cf.ro, "" AS "value"
			FROM `category_field` cf
			WHERE cf.category_id = :category_id
			ORDER BY cf.order'
		);
		$this->stmt->execute([':category_id' => $category_id]);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Obj');
	}
	public function insertObject($object_type_id) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO `object` (object_type_id) VALUES (:object_type_id)'
		);
		$this->stmt->execute([':object_type_id' => $object_type_id]);
		return $this->dbh->lastInsertId();
	}
	public function deleteObject($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM `object` WHERE id = :id'
		);
		if(!$this->stmt->execute([':id' => $id])) return false;
		return ($this->stmt->rowCount() != 1);
	}
	public function deleteCategorySet($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM `object_category_set` WHERE id = :id'
		);
		if(!$this->stmt->execute([':id' => $id])) return false;
		return ($this->stmt->rowCount() != 1);
	}

	public function insertObjectCategorySet($object_id, $category_id) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO `object_category_set` (object_id, category_id) VALUES (:object_id, :category_id)'
		);
		$this->stmt->execute([':object_id' => $object_id, ':category_id' => $category_id]);
		return $this->dbh->lastInsertId();
	}
	public function replaceObjectCategoryValue($object_category_set_id, $category_field_id, $value) {
		$this->stmt = $this->dbh->prepare(
			'REPLACE INTO `object_category_value` (object_category_set_id, category_field_id, `value`) VALUES (:object_category_set_id, :category_field_id, :value)'
		);
		return $this->stmt->execute([':object_category_set_id' => $object_category_set_id, ':category_field_id' => $category_field_id, ':value' => $value]);
	}

	// List View Operations
	public function selectAllListViewFieldByObjectTypeSystemUser($object_type_id, $system_user_id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT lvf.*, cf.category_id, cf.title FROM `list_view_field` lvf
			INNER JOIN list_view lv ON lv.id = lvf.list_view_id
			INNER JOIN category_field cf ON cf.id = lvf.category_field_id
			WHERE lv.object_type_id = :object_type_id AND lv.system_user_id = :system_user_id'
		);
		$this->stmt->execute(['system_user_id' => $system_user_id, 'object_type_id' => $object_type_id]);
		if($this->stmt->rowCount())
			return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ListViewField');

		$this->stmt = $this->dbh->prepare(
			'SELECT lvf.*, cf.category_id, cf.title FROM `list_view_field` lvf
			INNER JOIN list_view lv ON lv.id = lvf.list_view_id
			INNER JOIN category_field cf ON cf.id = lvf.category_field_id
			WHERE lv.object_type_id = :object_type_id AND lv.system_user_id IS NULL'
		);
		$this->stmt->execute(['object_type_id' => $object_type_id]);
		if($this->stmt->rowCount())
			return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\ListViewField');

		return [Models\ListViewField::initWithValues(1, 1, 'title')];
	}
	public function selectAllCategoryFieldValueByObject(array $object_ids, array $fields) {
		$fieldsSql = ['o.id']; $counter = 0;
		foreach($fields as $field) {
			$counter ++;
			$fieldsSql[] = '(SELECT GROUP_CONCAT(`value` SEPARATOR "\n") FROM object_category_value ocv INNER JOIN object_category_set ocs ON ocs.id = ocv.object_category_set_id WHERE ocs.object_id = o.id AND ocv.category_field_id = '.intval($field->category_field_id).' GROUP BY ocv.category_field_id)';
		}
		$inParams = self::compileSqlInValues($object_ids);
		$this->stmt = $this->dbh->prepare(
			'SELECT '.implode(',', $fieldsSql).' FROM `object` o WHERE o.id IN ('.$inParams[0].')'
		);
		$this->stmt->execute($inParams[1]);
		return $this->stmt->fetchAll(PDO::FETCH_NUM);
	}

	// System User Operations
	public function selectAllSystemUserRole() {
		$this->stmt = $this->dbh->prepare(
			'SELECT sur.*, (SELECT count(id) FROM system_user su WHERE su.system_user_role_id = sur.id) AS "system_user_count" FROM system_user_role sur ORDER BY name ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUserRole');
	}
	public function selectSystemUserRole($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT sur.*, (SELECT count(id) FROM system_user su WHERE su.system_user_role_id = sur.id) AS "system_user_count" FROM system_user_role sur
			WHERE sur.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUserRole') as $row) {
			return $row;
		}
	}
	public function insertSystemUserRole($name, $permissions) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO system_user_role (name, permissions) VALUES (:name, :permissions)'
		);
		$this->stmt->execute([
			':name' => $name,
			':permissions' => $permissions,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateSystemUserRole($id, $name, $permissions) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE system_user_role SET name = :name, permissions = :permissions WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':name' => $name,
			':permissions' => $permissions,
		]);
	}
	public function deleteSystemUserRole($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM system_user_role WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id]);
	}
	public function selectAllSystemUser() {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			ORDER BY username ASC'
		);
		$this->stmt->execute();
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUser');
	}
	public function selectSystemUser($id) {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			WHERE su.id = :id'
		);
		$this->stmt->execute([':id' => $id]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUser') as $row) {
			return $row;
		}
	}
	public function selectSystemUserByUsername($username) {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			WHERE su.username = :username'
		);
		$this->stmt->execute([':username' => $username]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUser') as $row) {
			return $row;
		}
	}
	public function selectSystemUserByUid($uid) {
		$this->stmt = $this->dbh->prepare(
			'SELECT su.*, sur.name AS "system_user_role_name", sur.permissions AS "system_user_role_permissions"
			FROM system_user su LEFT JOIN system_user_role sur ON su.system_user_role_id = sur.id
			WHERE su.uid = :uid'
		);
		$this->stmt->execute([':uid' => $uid]);
		foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\SystemUser') as $row) {
			return $row;
		}
	}
	public function insertSystemUser($uid, $username, $display_name, $password, $ldap, $email, $phone, $mobile, $description, $locked, $system_user_role_id) {
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO system_user (uid, username, display_name, password, ldap, email, phone, mobile, description, locked, system_user_role_id)
			VALUES (:uid, :username, :display_name, :password, :ldap, :email, :phone, :mobile, :description, :locked, :system_user_role_id)'
		);
		$this->stmt->execute([
			':uid' => $uid,
			':username' => $username,
			':display_name' => $display_name,
			':password' => $password,
			':ldap' => $ldap,
			':email' => $email,
			':phone' => $phone,
			':mobile' => $mobile,
			':description' => $description,
			':locked' => $locked,
			':system_user_role_id' => $system_user_role_id,
		]);
		return $this->dbh->lastInsertId();
	}
	public function updateSystemUser($id, $uid, $username, $display_name, $password, $ldap, $email, $phone, $mobile, $description, $locked, $system_user_role_id) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE system_user SET uid = :uid, username = :username, display_name = :display_name, password = :password, ldap = :ldap, email = :email, phone = :phone, mobile = :mobile, description = :description, locked = :locked, system_user_role_id = :system_user_role_id WHERE id = :id'
		);
		return $this->stmt->execute([
			':id' => $id,
			':uid' => $uid,
			':username' => $username,
			':display_name' => $display_name,
			':password' => $password,
			':ldap' => $ldap,
			':email' => $email,
			':phone' => $phone,
			':mobile' => $mobile,
			':description' => $description,
			':locked' => $locked,
			':system_user_role_id' => $system_user_role_id,
		]);
	}
	public function updateSystemUserLastLogin($id) {
		$this->stmt = $this->dbh->prepare('UPDATE system_user SET last_login = CURRENT_TIMESTAMP WHERE id = :id');
		return $this->stmt->execute([':id' => $id]);
	}
	public function deleteSystemUser($id) {
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM system_user WHERE id = :id'
		);
		return $this->stmt->execute([':id' => $id]);
	}

	// Log Operations
	public function insertLogEntry($level, $user, $object_id, $action, $data) {
		if($level < LOG_LEVEL) return;
		$this->stmt = $this->dbh->prepare(
			'INSERT INTO log (level, host, user, object_id, action, data)
			VALUES (:level, :host, :user, :object_id, :action, :data)'
		);
		$this->stmt->execute([
			':level' => $level,
			':host' => $_SERVER['REMOTE_ADDR'] ?? 'local',
			':user' => $user,
			':object_id' => $object_id,
			':action' => $action,
			':data' => is_array($data) ? json_encode($data) : $data,
		]);
		return $this->dbh->lastInsertId();
	}
	public function selectAllLogEntryByObjectIdAndActions($object_id, $actions, $limit=Models\Log::DEFAULT_VIEW_LIMIT) {
		if(empty($actions)) throw new Exception('Log filter: no action specified!');
		if(!is_array($actions)) $actions = [$actions];
		$actionSql = '(';
		$params = [];
		$counter = 0;
		foreach($actions as $action) {
			$counter ++;
			if($actionSql != '(') $actionSql .= ' OR ';
			$actionSql .= 'action LIKE :action'.$counter;
			$params[':action'.$counter] = $action.'%';
		}
		$actionSql .= ')';
		$this->stmt = $this->dbh->prepare(
			'SELECT * FROM log WHERE '.($object_id===null ? 'object_id IS NULL' : ($object_id===false ? '1=1' : 'object_id = :object_id')).' AND '.$actionSql.' ORDER BY timestamp DESC '.($limit ? 'LIMIT '.intval($limit) : '')
		);
		if($object_id !== null && $object_id !== false) {
			$params[':object_id'] = $object_id;
		}
		$this->stmt->execute($params);
		return $this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Log');
	}
	public function deleteLogEntryOlderThan($seconds) {
		if(intval($seconds) < 1) return;
		$this->stmt = $this->dbh->prepare(
			'DELETE FROM log WHERE timestamp < NOW() - INTERVAL '.intval($seconds).' SECOND'
		);
		if(!$this->stmt->execute()) return false;
		return $this->stmt->rowCount();
	}

	// Setting Operations
	public function selectSettingByKey($key) {
		try {
			$this->stmt = $this->dbh->prepare(
				'SELECT * FROM setting WHERE `key` = :key LIMIT 1'
			);
			$this->stmt->execute([':key' => $key]);
			foreach($this->stmt->fetchAll(PDO::FETCH_CLASS, 'Models\Setting') as $row) {
				return $row;
			}
		} catch(PDOException $ignored) {}
		return null;
	}
	public function insertOrUpdateSettingByKey($key, $value) {
		$this->stmt = $this->dbh->prepare(
			'UPDATE setting SET id = LAST_INSERT_ID(id), `value` = :value WHERE `key` = :key LIMIT 1'
		);
		$this->stmt->execute([':key' => $key, ':value' => $value]);
		if($this->dbh->lastInsertId()) return $this->dbh->lastInsertId();

		$this->stmt = $this->dbh->prepare(
			'INSERT INTO setting (`key`, `value`) VALUES (:key, :value)'
		);
		if(!$this->stmt->execute([':key' => $key, ':value' => $value])) return false;
		return $this->dbh->lastInsertId();
	}

}
