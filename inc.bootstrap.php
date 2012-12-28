<?php

// Always. UTF-8. Everywhere. Always.
header('Content-type: text/html; charset=utf-8');

// Some app constants
define('FORCE_JIRA_USER_SYNC', 600);
define('FORMAT_DATETIME', "d M 'y H:i");

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
define('JIRA_AUTH_PATH', '/auth/1/');
define('JIRA_API_PATH', '/api/2/');
define('JIRA_API_1_PATH', '/api/1.0/');

// Current session. This implementation is NOT ACCEPTABLE. See auth.php.
$user = null;
if ( isset($_COOKIE[session_name()]) ) {
	session_start();

	if ( isset($_SESSION['jira']['url'], $_SESSION['jira']['user'], $_SESSION['jira']['pass']) ) {
		define('JIRA_URL', $_SESSION['jira']['url']);
		define('JIRA_USER', $_SESSION['jira']['user']);
		define('JIRA_PASS', $_SESSION['jira']['pass']);

		$user = User::get();
	}
	else {
		$_SESSION['jira'] = null;
	}
}
