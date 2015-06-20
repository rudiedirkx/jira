<?php

// Always. UTF-8. Everywhere. Always.
header('Content-type: text/html; charset=utf-8');

// Some app constants
define('FORCE_JIRA_USER_SYNC', 600);
define('FORCE_AUTO_VARS_SYNC', 86400);
define('FORMAT_DATETIME', "j M 'y H:i");
define('FORMAT_DATE', "j M 'y");
define('WORKLOG_DATETIME', 'Y-m-d\\TH:i:s.000O');

// Context
require 'env.php';
require 'inc.functions.php';

// Database
require DB_INC_PATH . '/db_sqlite.php';
$db = db_sqlite::open(array('database' => DB_PATH));

$schema = require 'inc.schema.php';
$db->schema($schema);

// Classes
require 'inc.user.php';
require 'inc.issue.php';

// Jira API resource prefixes
define('JIRA_AUTH_PATH', '/rest/auth/1/');
define('JIRA_API_PATH', '/rest/api/2/');
define('JIRA_API_1_PATH', '/rest/api/1.0/');

// Request constants
define('IS_AJAX', strtolower(@$_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

// Init Jira request history
$jira_history = array();

// Current session
$user = null;
if ( isset($_COOKIE['JIRA_URL'], $_COOKIE['JIRA_AUTH']) && ($accounts = get_accounts()) ) {
	$account = $accounts[0];

	define('JIRA_URL', $account->url);
	define('JIRA_USER', $account->user);
	define('JIRA_AUTH', $account->auth);

	define('XSRF_TOKEN', md5(date('Y-m-d H') . ':' . JIRA_URL . ':' . JIRA_AUTH));

	$url = parse_url(JIRA_URL);
	define('JIRA_ORIGIN', $url['scheme'] . '://' . $url['host']);

	$user = User::load();
	if ( $user->jira_timezone ) {
		date_default_timezone_set($user->jira_timezone);
	}
}

// Frontpage / homepage / index page, to change the main menu items
$index = false;
