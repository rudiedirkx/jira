<?php

$_start = microtime(1);

// Always. UTF-8. Everywhere. Always.
header('Content-type: text/html; charset=utf-8');

const JIRA_COOKIE_NAME = 'JIRA_AUTH_2';

// Some app constants
define('FORCE_JIRA_USER_SYNC', 600); // 10m
define('FORCE_AUTO_VARS_SYNC', 14400); // 4h
define('FORMAT_DATETIME', "j M 'y H:i");
define('FORMAT_DATE', "j M 'y");
define('WORKLOG_DATETIME', 'Y-m-d\\TH:i:s.000O');

// Context
require 'env.php';
require 'vendor/autoload.php';
require 'inc.functions.php';

// Database
$db = db_sqlite::open(array('database' => DB_PATH));

$db->ensureSchema(require 'inc.schema.php');

// Classes
require 'inc.account.php';
require 'inc.user.php';
require 'inc.issue.php';

db_generic_model::$_db = $db;

// Jira API resource prefixes
define('JIRA_API_PATH', '/rest/api/2/');
define('JIRA_API_1_PATH', '/rest/api/1.0/');

// Request constants
define('IS_AJAX', strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest');

define('OAUTH_REDIRECT_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/oauth2-callback.php');

// Init Jira request history
$jira_history = array();

// Current session
$user = null;
if ( isset($_COOKIE[JIRA_COOKIE_NAME]) && count($accounts = get_accounts()) ) {
	$account = $accounts[0];

	define('JIRA_URL', $account->apiUrl);
	define('JIRA_AUTH', $account->auth);
	define('JIRA_USER', $account->username);
	define('JIRA_SERVER', $account->server);

	define('XSRF_TOKEN', md5(date('Y-m-d H') . ':' . JIRA_URL . ':' . JIRA_AUTH));

	$url = parse_url(JIRA_SERVER);
	define('JIRA_ORIGIN', $url['scheme'] . '://' . $url['host']);

	$user = User::load(JIRA_SERVER, JIRA_USER);
	if ( $user && $user->jira_timezone ) {
		date_default_timezone_set($user->jira_timezone);
	}
}

// Frontpage / homepage / index page, to change the main menu items
$index = false;
