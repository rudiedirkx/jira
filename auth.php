<?php

require 'inc.bootstrap.php';

// Log out
if ( isset($_GET['logout']) ) {
	do_logout();
	return do_redirect('index');
}

// Already logged in
if ( defined('JIRA_AUTH') ) {
	echo '<p>You are:</p>';

	// $account = jira_get('user', array('username' => JIRA_USER), $error, $info);
	// echo '<pre>';
	// print_r($account);
	// echo '</pre>';

	$session = jira_get(JIRA_AUTH_PATH . 'session', null, $error, $info);
	echo '<pre>';
	print_r($session);
	echo '</pre>';

	$account = jira_get($session->self, null, $error, $info);
	echo '<pre>';
	print_r($account);
	echo '</pre>';

	// Update timezone from Jira
	$db->update('users', array('jira_timezone' => $account->timeZone), array('id' => $user->id));

	exit;
}

// Log in
if ( isset($_POST['url'], $_POST['user'], $_POST['pass']) ) {
	// Test connection
	define('JIRA_URL', rtrim($_POST['url'], '/'));
	define('JIRA_USER', $_POST['user']);
	define('JIRA_AUTH', $_POST['user'] . ':' . $_POST['pass']);
	$info = array('unauth_ok' => 1);
	$account = jira_get('user', array('username' => JIRA_USER), $error, $info);

	// Invalid URL
	if ( $error == 404 ) {
		exit('Invalid URL?');
	}
	// Invalid credentials
	else if ( $error || empty($account->active) || empty($account->name) || $account->name !== JIRA_USER ) {
		exit('Invalid login?');
	}

	// Save user to local db for preferences
	try {
		$db->insert('users', array(
			'jira_url' => JIRA_URL,
			'jira_user' => JIRA_USER,
		));
	}
	catch ( db_exception $ex ) {
		// Let's assume it failed because the user already exists.
	}

	$user = User::load();
	$user->unsync();
	$db->update('users', array('jira_timezone' => $account->timeZone), array('id' => $user->id));

	// Save credentials to cookie
	$remember = !empty($_POST['remember']);
	$month = strtotime('+1 month');
	$time = $remember ? $month : 0;
	setcookie('JIRA_URL', JIRA_URL, $month);
	setcookie('JIRA_AUTH', do_encrypt(JIRA_AUTH), $time);

	return do_redirect('index');
}

?>
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
* { box-sizing: border-box; -webkit-box-sizing: border-box; }
input:not([type="submit"]):not([type="button"]):not([type="radio"]):not([type="checkbox"]), select { width: 100%; }
</style>

<form method="post">
	<p>Server URL: <input name="url" size="60" value="<?= @$_COOKIE['JIRA_URL'] ?>" placeholder="https://YOUR.jira.com/rest" /></p>
	<p>Username: <input name="user" /></p>
	<p>Password: <input name="pass" type="password" /></p>
	<p><label title="Will remember for 1 month or until you log out."><input name="remember" type="checkbox" /> Remember?</label></p>
	<p><input type="submit" /></p>
</form>
