<?php

require 'inc.bootstrap.php';

// Already logged in
if ( defined('JIRA_AUTH') ) {
	$_title = 'Auth';
	include 'tpl.header.php';

	echo '<p>You are:</p>';
	echo '<pre>';

	$info = ['unauth_ok' => true];
	$account = jira_get('/rest/api/3/myself', null, $error, $info);
	print_r($account);
	if ($error) {
		echo $info['response'] . "\n";
		print_r($info);
		exit;
	}

	print_r($user);

	// Update timezone from Jira
	if ( $account ) {
		$user->update(array('jira_timezone' => $account->timeZone));
	}

	print_r($user->custom_field_ids);

	echo '</pre>';
	include 'tpl.footer.php';
	exit;
}

// Log in
if ( isset($_POST['url'], $_POST['user'], $_POST['pass']) ) {
	// Test connection
	$url = trim($_POST['url'], ' /');
	$username = trim($_POST['user']);
	if ( !jira_test($url, $username, $_POST['pass'], $info) ) {
		exit($info['error2']);
	}
	$jiraUsername = $info['session'];

	// Save user to local db for preferences
	try {
		$db->insert('users', array(
			'jira_url' => $url,
			'jira_user' => $username,
			'created' => time(),
		));
	}
	catch ( db_exception $ex ) {
		// Let's assume it failed because the user already exists.
	}

	define('JIRA_URL', $info['JIRA_URL']);
	define('JIRA_USER', $username);
	define('JIRA_AUTH', $info['JIRA_AUTH']);

	$user = User::load($url, $username);
	$user->unsync();
	// $db->update('users', array('jira_timezone' => $jiraUsername->timeZone), array('id' => $user->id));

	// Save credentials to cookie
	do_login($url, $info['JIRA_AUTH'], $username);

	return do_redirect('index');
}

$_title = 'Auth';
include 'tpl.header.php';

?>
<p>All sensitive data is stored in a cookie ON YOUR COMPUTER. NOT on this server.</p>

<? include 'tpl.login.php' ?>

<p>Make a <em>Personal API token</em> on <a href="https://id.atlassian.com/manage/api-tokens">https://id.atlassian.com/manage/api-tokens</a>.</p>

<p><a href="oauth2-start.php">Or try the brandnew Cloud OAuth2 experience!</a></p>
