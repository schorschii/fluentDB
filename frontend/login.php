<?php
require_once('../loader.inc.php');

// redirect to setup if setup is not done
if(!$db->existsSchema() || !$db->isEstablished()) {
	header('Location: setup.php');
	die();
}

// init page objects/variables
$license = new LicenseCheck($db);
$loginScreenQuotes = json_decode($db->settings->get('login-screen-quotes'), true);

$info = null;
$infoclass = null;

// execute login if requested
require_once('session-options.inc.php');
if(isset($_POST['username']) && isset($_POST['password'])) {
	try {
		$authenticator = new AuthenticationController($db);
		$user = $authenticator->login($_POST['username'], $_POST['password']);
		if($user == null || !$user instanceof Models\SystemUser) throw new Exception(LANG('unknown_error'));

		$cl1 = new CoreLogic($db, $user);
		if(!$cl1->checkPermission(null, PermissionManager::SPECIAL_PERMISSION_CLIENT_WEB_FRONTEND, false)) {
			throw new AuthenticationException(LANG('web_interface_login_not_allowed'));
		}

		// login successful
		$db->insertLogEntry(Models\Log::LEVEL_INFO, $user->username, null, Models\Log::ACTION_CLIENT_WEB, ['authenticated'=>true]);
		$_SESSION['fluentdb_username'] = $user->username;
		$_SESSION['fluentdb_user_id'] = $user->id;

		$redirect = 'index.php';
		if(!empty($_SESSION['fluentdb_login_redirect'])) $redirect = $_SESSION['fluentdb_login_redirect'];
		header('Location: '.$redirect); die('Welcome to the enchanting world of FluentDB!');
	} catch(AuthenticationException $e) {
		$db->insertLogEntry(Models\Log::LEVEL_WARNING, $_POST['username'], null, Models\Log::ACTION_CLIENT_WEB, ['authenticated'=>false]);

		$info = $e->getMessage();
		$infoclass = 'error';
	}
}

// execute logout if requested
elseif(isset($_GET['logout'])) {
	if(isset($_SESSION['fluentdb_user_id'])) {
		$db->insertLogEntry(Models\Log::LEVEL_INFO, $_SESSION['fluentdb_username'], null, Models\Log::ACTION_CLIENT_WEB, ['logout'=>true]);
		session_unset();
		session_destroy();
		$info = LANG('log_out_successful');
		$infoclass = 'success';
	}
}

// redirect to index.php if already logged in
if(!empty($_SESSION['fluentdb_user_id'])) {
	header('Location: index.php');
	die();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>[<?php echo LANG('login'); ?>] <?php echo LANG('project_name'); ?></title>
	<?php require_once('head.inc.php'); ?>
</head>
<body>

<div id='container'>

	<div id='header'>
		<span class='left'>
			<a href='#' class='title'><?php echo LANG('project_name'); ?></a>
		</span>
		<span class='right'>
		</span>
	</div>

	<div id='login'>
		<div id='login-form'>
			<?php if(isIE()) { ?>
				<img src='img/ietroll.png'>
			<?php } else { ?>
				<form method='POST' onsubmit='btnLogin.disabled=true; txtUsername.readOnly=true; txtPassword.readOnly=true;'>
					<h1><?php echo LANG('login'); ?></h1>
					<?php if($info !== null) { ?>
						<div class='alert bold <?php echo $infoclass; ?>'><?php echo $info; ?></div>
					<?php } ?>
					<input id='txtUsername' type='text' name='username' placeholder='<?php echo LANG('username'); ?>' autofocus='true'>
					<input id='txtPassword' type='password' name='password' placeholder='<?php echo LANG('password'); ?>'>
					<button id='btnLogin' class='primary'><?php echo LANG('log_in'); ?></button>
				</form>
				<img src='img/logo.dyn.svg'>
			<?php } ?>
		</div>

		<div id='login-wall'>
			<div id='login-bg'></div>
			<a href='https://github.com/schorschii/fluentdb' target='_blank'>
				<img id='forkme' src='img/forkme.png'>
			</a>
			<div id='motd'><?php if(!empty($loginScreenQuotes)) echo $loginScreenQuotes[ rand(0, sizeof($loginScreenQuotes)-1) ]; ?></div>
		</div>
	</div>

	<button id='btnHidden' onclick='toggleEquip()'></button>

</div>

</body>
</html>
