<?php

class LdapSync {

	private /*DatabaseController*/ $db;
	private /*bool*/ $debug;

	function __construct(DatabaseController $db, bool $debug=false) {
		$this->db = $db;
		$this->debug = $debug;
	}

	private static function GUIDtoStr($binary_guid) {
		$unpacked = unpack('Va/v2b/n2c/Nd', $binary_guid);
		if(!$unpacked) {
			// fallback string representation (base64) if we got unexpected input
			return base64_encode($binary_guid);
		}
		return sprintf('%08x-%04x-%04x-%04x-%04x%08x', $unpacked['a'], $unpacked['b1'], $unpacked['b2'], $unpacked['c1'], $unpacked['c2'], $unpacked['d']);
	}

	public function syncUsers() {
		if(!LDAP_SERVER) {
			throw new Exception('LDAP sync not configured');
		}

		$ldapconn = ldap_connect(LDAP_SERVER);
		if(!$ldapconn) {
			throw new Exception('ldap_connect failed');
		}

		if($this->debug) echo "<=== ldap_connect OK ===>\n";
		ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 5);
		ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0 );
		$ldapbind = ldap_bind($ldapconn, LDAP_USERNAME, LDAP_PASSWORD);
		if(!$ldapbind) {
			throw new Exception('ldap_bind failed: '.ldap_error($ldapconn));
		}

		if($this->debug) echo "<=== ldap_bind OK ===>\n";
		$result = ldap_search($ldapconn, LDAP_QUERY_ROOT, LDAP_FILTER);
		if(!$result) {
			throw new Exception('ldap_search failed: '.ldap_error($ldapconn));
		}

		$data = ldap_get_entries($ldapconn, $result);

		if($this->debug) echo "<=== ldap_search OK - processing ".$data["count"]." entries... ===>\n";

		// iterate over results array
		$foundLdapUsers = [];
		$counter = 1;
		for($i=0; $i<$data['count']; $i++) {
			#var_dump($data[$i]); die(); // debug

			if(empty($data[$i][LDAP_ATTR_UID][0])) {
				continue;
			}
			$uid = self::GUIDtoStr($data[$i][LDAP_ATTR_UID][0]);
			if(array_key_exists($uid, $foundLdapUsers)) {
				throw new Exception('Duplicate UID '.$uid.'!');
			}

			// parse LDAP values
			$username = $data[$i][LDAP_ATTR_USERNAME][0];
			$title    = '?';
			if(isset($data[$i][LDAP_ATTR_TITLE][0]))
				$title = $data[$i][LDAP_ATTR_TITLE][0];

			// add to found array
			$foundLdapUsers[$uid] = $username;

			// check if user already exists
			$id = null;
			$checkResult = $this->db->selectAllObjectByCategoryFieldValue(CoreLogic::LOGIN_CATEGORY_ID, CoreLogic::UNIQUE_IDENTIFIER_FIELD_ID, $uid);
			if(count($checkResult) > 1) {
				if($this->debug) echo '--> '.$uid.': found multiple in db - skipping!';
			} elseif(count($checkResult) == 1) {
				$id = $checkResult[0]->id;
				if($this->debug) echo '--> '.$username.': found in db - update id: '.$id;

				// update into db
				# TODO
				if($this->debug) echo "  SKIP\n";
			} else {
				if($this->debug) echo '--> '.$username.': not found in db - creating';

				// insert into db
				$objId = $this->db->insertObject(CoreLogic::OBJTYPE_PERSON_ID);
				$setId = $this->db->insertObjectCategorySet($objId, CoreLogic::GENERAL_CATEGORY_ID);
				$this->db->replaceObjectCategoryValue($setId, CoreLogic::TITLE_FIELD_ID, $title);
				$setId = $this->db->insertObjectCategorySet($objId, CoreLogic::LOGIN_CATEGORY_ID);
				$this->db->replaceObjectCategoryValue($setId, CoreLogic::USERNAME_FIELD_ID, $username);
				$this->db->replaceObjectCategoryValue($setId, CoreLogic::UNIQUE_IDENTIFIER_FIELD_ID, $uid);
				if($this->debug) echo "  OK\n";
			}
			$counter ++;
		}
		ldap_close($ldapconn);

		if($this->debug) echo "<=== Check For Deleted Users... ===>\n";
		$ids = [];
		foreach($this->db->selectAllObjectByObjectType(CoreLogic::OBJTYPE_PERSON_ID) as $object)
			$ids[] = $object->id;
		$fields = [
			Models\ListViewField::initWithValues(CoreLogic::UNIQUE_IDENTIFIER_FIELD_ID, CoreLogic::LOGIN_CATEGORY_ID, null),
			Models\ListViewField::initWithValues(CoreLogic::USERNAME_FIELD_ID, CoreLogic::LOGIN_CATEGORY_ID, null),
		];
		foreach($this->db->selectAllCategoryFieldValueByObject($ids, $fields) as $dbUser) {
			$dbUserUid = $dbUser[1];
			$dbUserUsername = $dbUser[2];
			if(empty($dbUserUid)) continue; // it's not an LDAP user

			$found = false;
			foreach($foundLdapUsers as $uid => $username) {
				if($dbUserUid == $uid) {
					$found = true; break;
				}
				if($dbUserUsername == $username) { // fallback for old DB schema without uid
					$found = true; break;
				}
			}
			if(!$found) {
				if($this->db->deleteObject($dbUser[0])) {
					if($this->debug) echo '--> '.$dbUserUsername.': deleting  OK'."\n";
				}
				else throw new Exception('Error deleting '.$dbUserUsername.': '.$this->db->getLastStatement()->error);
			}
		}
	}

}
