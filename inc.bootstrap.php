<?php

// Always. UTF-8. Everywhere. Always.
header('Content-type: text/html; charset=utf-8');

// Some app constants
define('FORCE_JIRA_USER_SYNC', 600);
define('FORMAT_DATETIME', "d M 'y H:i");
define('FORMAT_DATE', "d M 'y");
define('WORKLOG_DATETIME', 'Y-m-d\\TH:i:s.000O');

// Context
require 'env.php';
require 'inc.functions.php';

// Database
require DB_INC_PATH . '/db_sqlite.php';
$db = db_sqlite::open(array('database' => DB_PATH));

$schema = require 'inc.schema.php';
$db->schema($schema);

// User class
require 'inc.user.php';

// Jira API resource prefixes
define('JIRA_AUTH_PATH', '/rest/auth/1/');
define('JIRA_API_PATH', '/rest/api/2/');
define('JIRA_API_1_PATH', '/rest/api/1.0/');

// Current session
$user = null;
if ( isset($_COOKIE['JIRA_URL'], $_COOKIE['JIRA_AUTH']) && ($accounts = get_accounts()) ) {
	$account = $accounts[0];
// print_r($account);
// print_r($_COOKIE);

	define('JIRA_URL', $account->url);
	define('JIRA_USER', $account->user);
	define('JIRA_AUTH', $account->auth);

	$user = User::load();
	if ( $user->jira_timezone ) {
		date_default_timezone_set($user->jira_timezone);
	}
}

$index = false;
