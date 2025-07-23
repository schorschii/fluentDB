<?php

class AuthenticationController {

	/*
		 Class AuthenticationController
		 Handles Login Requests
	*/

	private $db;

	function __construct($db) {
		$this->db = $db;
	}

	/*** Authentication Logic ***/
	public function login($username, $password) {
		$users = $this->db->selectAllObjectByCategoryFieldValue(CoreLogic::LOGIN_CATEGORY_ID, CoreLogic::USERNAME_FIELD_ID, $username);
		if(empty($users)) {
			sleep(2); // delay to avoid brute force attacks
			throw new AuthenticationException(LANG('user_does_not_exist'));
		}
		if(count($users) > 1) {
			throw new AuthenticationException(LANG('username_is_not_unique'));
		}
		$user = $users[0];
		$locked = boolval($this->db->selectAllValueByObjectCategoryField($user->id, CoreLogic::LOGIN_CATEGORY_ID, CoreLogic::DISABLED_LOGIN_FIELD_ID));
		$lastLogin = $this->db->selectAllValueByObjectCategoryField($user->id, CoreLogic::LOGIN_CATEGORY_ID, CoreLogic::LAST_LOGIN_FIELD_ID);
		if(!$locked) {
			if($this->checkPassword($user, $password)) {
				$cl = new CoreLogic($this->db);
				$cl->updateCategories($user->id, [
					new Models\UpdateField(CoreLogic::LOGIN_CATEGORY_ID, CoreLogic::LAST_LOGIN_FIELD_ID, -1, date('Y-m-d H:i:s')),
				], true);
				return [$user, $lastLogin];
			} else {
				sleep(2);
				throw new AuthenticationException(LANG('login_failed'));
			}
		} else {
			sleep(1);
			throw new AuthenticationException(LANG('user_locked'));
		}
		return false;
	}
	private function checkPassword($userObject, $checkPassword) {
		$result = $this->validatePassword($userObject, $checkPassword);
		if(!$result) {
			// log for fail2ban
			error_log('user '.$userObject->id.': authentication failure');
		}
		return $result;
	}
	private function validatePassword($userObject, $checkPassword) {
		$userLdap = !empty($this->db->selectAllValueByObjectCategoryField($userObject->id, CoreLogic::LOGIN_CATEGORY_ID, CoreLogic::UNIQUE_IDENTIFIER_FIELD_ID));
		if($userLdap) {
			// do not allow anonymous binds
			if(empty($checkPassword)) return false;

			$userUsername = $this->db->selectAllValueByObjectCategoryField($userObject->id, CoreLogic::LOGIN_CATEGORY_ID, CoreLogic::USERNAME_FIELD_ID);

			// get DN for LDAP auth check if configured
			$binddnQuery = empty(LDAP_BINDDN_QUERY) ? '(&(objectClass=user)(samaccountname=%s))' : LDAP_BINDDN_QUERY;
			if($binddnQuery) {
				$ldapconn1 = ldap_connect(LDAP_SERVER);
				if(!$ldapconn1) throw new AuthenticationException('ldap_connect failed for binddn query');
				ldap_set_option($ldapconn1, LDAP_OPT_PROTOCOL_VERSION, 3);
				ldap_set_option($ldapconn1, LDAP_OPT_NETWORK_TIMEOUT, 3);
				$ldapbind = ldap_bind($ldapconn1, LDAP_USERNAME, LDAP_PASSWORD);
				if(!$ldapbind) throw new AuthenticationException('ldap_bind failed for binddn query');
				$result = ldap_search($ldapconn1, LDAP_QUERY_ROOT, str_replace('%s', ldap_escape($userUsername), $binddnQuery), ['dn']);
				if(!$result) throw new AuthenticationException('ldap_search failed for binddn query');
				$data = ldap_get_entries($ldapconn1, $result);
				if(!empty($data[0]['dn'])) $userUsername = $data[0]['dn'];
			}

			// try user authentication
			$ldapconn = ldap_connect(LDAP_SERVER);
			if(!$ldapconn) return false;
			ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, 3);
			$ldapbind = @ldap_bind($ldapconn, $userUsername, $checkPassword);
			if(!$ldapbind) return false;
			return true;
		} else {
			$userPassword = $this->db->selectAllValueByObjectCategoryField($userObject->id, CoreLogic::LOGIN_CATEGORY_ID, CoreLogic::PASSWORD_FIELD_ID);
			return password_verify($checkPassword, $userPassword);
		}
	}

}
