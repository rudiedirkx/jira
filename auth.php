<?php

require 'inc.bootstrap.php';

// Log out
if ( isset($_GET['logout']) ) {
	@session_start();
	$_SESSION['jira'] = null;
	exit('OK');
}

// Already logged in
if ( defined('JIRA_URL') ) {
	echo '<p>You are:</p>';

	// $user = jira_get('user', array('username' => JIRA_USER), $error, $info);
	// echo '<pre>';
	// print_r($user);
	// echo '</pre>';

	$session = jira_get(JIRA_AUTH_PATH . 'session', null, $error, $info);
	echo '<pre>';
	print_r($session);
	echo '</pre>';

	$user = jira_get($session->self, null, $error, $info);
	echo '<pre>';
	print_r($user);
	echo '</pre>';

	exit;
}

// Log in
if ( isset($_POST['url'], $_POST['user'], $_POST['pass']) ) {
	// Test connection
	define('JIRA_URL', rtrim($_POST['url'], '/'));
	define('JIRA_USER', $_POST['user']);
	define('JIRA_PASS', $_POST['pass']);
	$session = jira_get(JIRA_AUTH_PATH . 'session', null, $error, $info);

	// Invalid URL
	if ( $error == 404 ) {
		exit('Invalid URL?');
	}
	// Invalid URL
	else if ( $error || empty($session->name) || $session->name !== JIRA_USER ) {
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
		// Let's assume it failed because the user already exists and not because of
		// some database error. The rest of the app will keep in mind the global user
		// might not exist.
	}

	// Save URL to cookie for easy access next time
	setcookie('JIRA_URL', JIRA_URL, strtotime('+1 month'));

	// Save basic auth in session
	// This is NOT ACCEPTABLE because sessions are stored on the filesystem. Acceptable
	// would be setting a cookie with ENCRYPTED basic auth. Encrypted with a local secret
	// from env.php.
	@session_start();
	$_SESSION['jira'] = array(
		'url' => JIRA_URL,
		'user' => JIRA_USER,
		'pass' => JIRA_PASS,
	);

	return do_redirect('index');
}

?>
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
<form method="post">
	<p>Server URL: <input name="url" size="60" value="<?= @$_COOKIE['JIRA_URL'] ?>" placeholder="https://YOUR.jira.com/rest" /></p>
	<p>Username: <input name="user" /></p>
	<p>Password: <input name="pass" type="password" /></p>
	<p><input type="submit" /></p>
</form>
