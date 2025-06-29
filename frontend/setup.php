<?php
require_once('../loader.inc.php');

$info = null;
$infoclass = null;
$step = 0;

// exit if setup was already done
if(!$db->existsSchema()) {
	$info = LANG('please_import_database_schema_first');
	$infoclass = 'warning';
} else {
	if($db->isEstablished()) {
		header('Location: index.php');
		die();
	} else {
		$step = 1;
	}
}

// create initial admin user
if(isset($_POST['username'])
&& isset($_POST['password']) && isset($_POST['password2'])) {
	if(empty(trim($_POST['username']))) {
		$info = LANG('username_cannot_be_empty');
		$infoclass = 'error';
	} elseif(empty(trim($_POST['password']))) {
		$info = LANG('password_cannot_be_empty');
		$infoclass = 'error';
	} elseif($_POST['password'] !== $_POST['password2']) {
		$info = LANG('passwords_do_not_match');
		$infoclass = 'error';
	} elseif($db->existsSchema() && count($db->selectAllSystemUser()) == 0) {
		$db->insertSystemUser(
			md5(rand()), $_POST['username'], $_POST['username'],
			password_hash($_POST['password'], PASSWORD_DEFAULT),
			0/*ldap flag*/, null, null, null, 'initial admin user', 0/*locked*/,
			1/*default role: superadmin*/
		);
		header('Location: login.php');
		die();
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>[<?php echo LANG('setup'); ?>] <?php echo LANG('project_name'); ?></title>
	<?php require_once('head.inc.php'); ?>
</head>
<body>

<div id='container'>

	<div id='header'>
		<span class='left'><span class='title'><?php echo LANG('project_name'); ?></span></span>
		<span class='right'>
		</span>
	</div>

	<div id='login'>
		<div id='login-form'>
			<form method='POST' action='setup.php' onsubmit='btnFinish.disabled=true'>
				<h1><?php echo LANG('setup'); ?></h1>
				<?php if($info !== null) { ?>
					<div class='alert bold <?php echo $infoclass; ?>'><?php echo $info; ?></div>
				<?php } ?>
				<?php if($step == 1) { ?>
					<input type='text' name='username' required='true' placeholder='<?php echo LANG('choose_admin_username'); ?>' autofocus='true'>
					<input type='password' name='password' required='true' placeholder='<?php echo LANG('choose_admin_password'); ?>'>
					<input type='password' name='password2' required='true' placeholder='<?php echo LANG('confirm_admin_password'); ?>'>
					<button id='btnFinish'><?php echo LANG('done'); ?></button>
				<?php } ?>
			</form>
			<img src='img/logo.dyn.svg'>
		</div>
		<div id='login-wall'>
			<div id='login-bg'></div>
			<a href='https://github.com/schorschii/fluentdb' target='_blank'>
				<img id='forkme' src='img/forkme.png'>
			</a>
		</div>
	</div>

</div>

</body>
</html>
