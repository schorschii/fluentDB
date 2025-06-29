<?php
require_once('../loader.inc.php');

// check API enabled
if(!$db->settings->get('api-enabled')) {
	header('HTTP/1.1 405 API Disabled'); die();
}

// check content type
if(!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] != 'application/json') {
	header('HTTP/1.1 400 Content Type Mismatch'); die();
}

// handle BREW requests
if(isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'BREW') {
	header("HTTP/1.1 418 I'm a teapot");
	die(
		'This is a teapot! But luckily it can handle coffee too.'."\n".
		'The local brewing personnel was informed about your coffee request.'."\n".
		'Please be patient while the CPU is heating up to brew your coffee.'."\n".
		'Meanwhile, here is an ASCII cup for you. Attention: Hot!'."\n".
		"\n".
		"         {"."\n".
		"      {   }"."\n".
		"       }_{ __{"."\n".
		"    .-{   }   }-."."\n".
		"   (   }     {   )"."\n".
		"   |`-.._____..-'|"."\n".
		"   |             ;--."."\n".
		"   |            (__  \\"."\n".
		"   |             | )  )"."\n".
		"   |             |/  /"."\n".
		"   |             /  /    -Felix Lee-"."\n".
		"   |            (  /"."\n".
		"   \             y'"."\n".
		"    `-.._____..-'"."\n".
		"\n"
	);
}

// login
$cl = null;
$user = null;
try {
	$username = null; $password = null;
	if(!empty($_SERVER['HTTP_X_RPC_AUTH_SESSION'])) {
		session_id($_SERVER['HTTP_X_RPC_AUTH_SESSION']);
		session_start();
		$username = $_SERVER['HTTP_X_RPC_AUTH_SESSION'];
		// TODO: get $user by session id
	} else {
		session_start();
		if(!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
			$username = $_SERVER['PHP_AUTH_USER'];
			$password = $_SERVER['PHP_AUTH_PW'];
		}
		if(!empty($_SERVER['HTTP_X_RPC_AUTH_USERNAME']) && !empty($_SERVER['HTTP_X_RPC_AUTH_PASSWORD'])) {
			$username = $_SERVER['HTTP_X_RPC_AUTH_USERNAME'];
			$password = $_SERVER['HTTP_X_RPC_AUTH_PASSWORD'];
		}
		if(empty($username) || empty($password)) {
			throw new AuthenticationException(LANG('username_cannot_be_empty'));
		}
		$authenticator = new AuthenticationController($db);
		$user = $authenticator->login($username, $password);
		if($user == null || !$user instanceof Models\SystemUser) {
			throw new AuthenticationException(LANG('unknown_error'));
		}

		#$cl1 = new CoreLogic($db, $user);
		#if(!$cl1->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_CLIENT_API, false)) {
		#	throw new AuthenticationException(LANG('api_login_not_allowed'));
		#}
	}

	// login successful
	$cl = new CoreLogic($db, $user);
	$db->insertLogEntry(Models\Log::LEVEL_INFO, $username, null, Models\Log::ACTION_CLIENT_API, ['authenticated'=>true]);
} catch(AuthenticationException $e) {
	$db->insertLogEntry(Models\Log::LEVEL_WARNING, $_SERVER['PHP_AUTH_USER'] ?? $_SERVER['HTTP_X_RPC_AUTH_USERNAME'] ?? '', null, Models\Log::ACTION_CLIENT_API, ['authenticated'=>false]);

	header('HTTP/1.1 401 Client Not Authorized');
	error_log('api-client: authentication failure');
	die('HTTP Basic Auth: '.$e->getMessage());
}

// get body
$body = file_get_contents('php://input');
$srcdata = json_decode($body, true);
if(empty($srcdata)) {
	header('HTTP/1.1 400 Payload Corrupt'); die();
}

// log complete request
$db->insertLogEntry(Models\Log::LEVEL_DEBUG, null, null, Models\Log::ACTION_CLIENT_API_RAW, $body);

// handle JSON-RPC request(s)
$resdata = [];
$multi = false;

if(array_is_list($srcdata)) {
	$multi = true;
	foreach($srcdata as $jsonObject) {
		$resdata[] = handleJsonRequest($jsonObject);
	}
} else {
	$resdata[] = handleJsonRequest($srcdata);
}

// return response(s)
header('Content-Type: application/json');
#error_log(json_encode($multi ? $resdata : $resdata[0], JSON_PARTIAL_OUTPUT_ON_ERROR));
echo json_encode($multi ? $resdata : $resdata[0], JSON_PARTIAL_OUTPUT_ON_ERROR);


function handleJsonRequest($request) {
	global $db, $cl, $ext;

	// validate JSON-RPC
	if(empty($request)
	|| !isset($request['jsonrpc']) || $request['jsonrpc'] != '2.0'
	|| !isset($request['method']) || !isset($request['id'])) {
		header('HTTP/1.1 400 Payload Corrupt'); die();
	}
	$params = $request['params'] ?? [];
	$data = $params['data'] ?? [];

	// execute method
	$response = ['jsonrpc' => '2.0', 'id' => $request['id']];

	try {

		// check API key
		$apiKey = $db->settings->get('api-key');
		if(!empty($apiKey) && $apiKey !== ($params['api_key'] ?? $params['apikey'] ?? '')) {
			throw new PermissionException(LANG('invalid_api_key'));
		}

		// handle method
		switch($request['method']) {
			case 'idoit.login':
			case 'fluentdb.login':
				$response['result'] = [
					'success' => true, 'session-id' => session_id(),
				];
				break;

			case 'cmdb.object_types':
				$objectTypes = [];
				foreach($db->selectAllObjectTypeByObjectTypeGroup(null) as $ot) {
					$objectTypes[] = [
						'id' => strval($ot->id),
						'title' => $ot->title,
						'container' => 0,
						'color' => '000000',
						'image' => base64_encode($ot->image),
						'icon' => '',
						'tree_group' => 0,
						'type_group' => $ot->object_type_group_id,
						'type_group_title' => '',
						'objectcount' => 0,
					];
				}
				$response['result'] = $objectTypes;
				break;

			case 'cmdb.object.create':
				$db->insertObject();
				$response['result'] = [
					'success' => true, 'data' => []
				];
				break;

			case 'idoit.search':
			case 'fluentdb.search':
				$results = [];
				foreach($db->searchAllObject($params['q'] ?? '') as $r) {
					$results[] = [
						'documentId' => strval($r->id),
						'key' => 'title',
						'value' => $r->title,
						'type' => 'cmdb',
						'link' => '/index.php?view=object&id='.$r->id,
						'score' => 1,
						'status' => 'Normal',
					];
				}
				$response['result'] = $results;
				break;

			case 'cmdb.object':
			case 'cmdb.object.read':
				$object = $db->selectObject($params['id'] ?? -1);
				$title = ''; $sysid = '';
				foreach($db->selectAllCategorySetByCategoryObject(1, $object->id) as $cs)
					foreach($db->selectAllCategoryValueByCategorySet($cs->id, $object->id) as $cv) {
						if($cv->constant == 'title') $title = $cv->value;
						elseif($cv->constant == 'sysid') $sysid = $cv->value;
					}
				$response['result'] = [
					'id' => $object->id,
					'title' => $title,
					'sysid' => $sysid,
					'objecttype' => $object->object_type_id,
					'type_title' => '',
					'type_icon' => '',
					'status' => 1,
					'cmdb_status' => 1,
					'cmdb_status_title' => 'in operation',
					'created' => date('Y-m-d H:i:s'),
					'updated' => date('Y-m-d H:i:s'),
					'image' => '',
				];
				break;

			case 'cmdb.object_type_categories':
				$categories = [];
				foreach($db->selectAllCategoryByObjectType($params['type'] ?? -1) as $c) {
					$categories[] = [
						'id' => $c->id,
						'const' => $c->constant,
						'title' => $c->title,
						'multi_value' => strval($c->multivalue),
						'source_table' => '',
					];
				}
				$response['result'] = [
					'catg' => $categories,
					'cats' => [],
					'custom' => [],
				];
				break;

			case 'cmdb.category_info':
				$response['result'] = [];
				$categoryId = $params['catgID'] ?? $params['catsID'] ?? $params['customID'] ?? null;
				if(!$categoryId) {
					$category = $db->selectCategoryByConstant($params['category'] ?? '');
					if(!$category) throw new NotFoundException();
					$categoryId = $category->id;
				}
				foreach($db->selectAllCategoryFieldByCategory($categoryId) as $cf) {
					$response['result'][$cf->constant] = [
						'title' => $cf->title,
						'check' => ['mandatory' => false],
						'info' => [
							'primary_field' => false,
							'type' => 'text',
							'backward' => false,
							'title' => $cf->title,
							'description' => '',
						],
						'data' => [
							'type' => 'text',
							'readonly' => false,
							'index' => false,
							'field' => '',
						],
						'ui' => [
							'type' => 'text',
							'default' => null,
							'params' => ['p_nMaxLen' => 255],
							'id' => $cf->title,
						],
					];
				}
				break;

			case 'cmdb.category':
			case 'cmdb.category.read':
				$values = [];
				$category = $db->selectCategoryByConstant($params['category'] ?? -1);
				if(!$category) throw new NotFoundException();
				foreach($db->selectAllCategorySetByCategoryObject($category->id, $params['objID'] ?? -1) as $cs) {
					$value = [
						'id' => $cs->id,
						'objID' => $params['objID'],
					];
					foreach($db->selectAllCategoryValueByCategorySet($cs->id) as $v) {
						$value[$v->constant] = $v->value;
					}
					$values[] = $value;
				}
				$response['result'] = $values;
				break;

			case 'cmdb.category.update':
				if(empty($data) || empty($params['category']) || empty($params['objID']))
					throw new InvalidRequestException();
				$category = $db->selectCategoryByConstant($params['category']);
				if(!$category)
					throw new NotFoundException();
				$updates = [];
				foreach($data as $key => $value) {
					if($key == 'category_id') continue;
					$field = $db->selectCategoryFieldByCategoryConstant($category->id, $key);
					if(!$field)
						throw new NotFoundException();
					$updates[] = [
						'category' => $category->id,
						'set' => $data['category_id'] ?? -1,
						'field' => $field->id,
						'value' => $value,
					];
				}
				$cl->updateCategories(intval($params['objID']), $updates);
				$response['result'] = [
					'success' => true, 'message' => 'Category entry successfuly saved.'
				];
				break;

			case 'cmdb.category.delete':
				$response['result'] = [
					'success' => true, 'data' => []
				];
				break;

			case 'cmdb.objects.read': // TODO
				$response['result'] = [
					'success' => true, 'data' => []
				];
				break;

			case 'cmdb.dialog.read': // TODO
				$response['result'] = [
					'success' => true, 'data' => [ 'id' => $insertId ]
				];
				break;

			case 'cmdb.reports.read': // TODO
				$response['result'] = [
					'success' => true, 'data' => []
				];
				break;

			case 'cmdb.objects_by_relation': // TODO
				$response['result'] = [
					'success' => true, 'data' => []
				];
				break;

			case 'cmdb.object.archive': // TODO
				$response['result'] = [
					'success' => true, 'data' => []
				];
				break;

			default:
				$extensionMethods = $ext->getAggregatedConf('client-api-methods');
				if(array_key_exists($srcdata['method'], $extensionMethods)) {
					$response['result'] = call_user_func($extensionMethods[$srcdata['method']], $data, $cl, $db);
				} else {
					throw new InvalidRequestException(LANG('unknown_method'));
				}
		}

	} catch(NotFoundException $e) {
		$response['error'] = ['code'=> -32003, 'message'=>LANG('not_found'), 'data'=>null];
	} catch(PermissionException $e) {
		$response['error'] = ['code'=> -32002, 'message'=>LANG('permission_denied'), 'data'=>null];
	} catch(InvalidRequestException $e) {
		$response['error'] = ['code'=> -32001, 'message'=>$e->getMessage(), 'data'=>null];
	} catch(Exception $e) {
		$response['error'] = ['code'=> -32000, 'message'=>$e->getMessage(), 'data'=>null];
	}
	return $response;
}
