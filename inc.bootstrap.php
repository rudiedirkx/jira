<?php

// Always. UTF-8. Everywhere. Always.
header('Content-type: text/html; charset=utf-8');

// Context
require 'env.php';
require 'inc.functions.php';

// Database
require DB_INC_PATH . '/db_sqlite.php';
$db = db_sqlite::open(array('database' => DB_PATH));

$schema = require 'inc.schema.php';
$db->schema($schema);

// Jira API resource prefixes
define('JIRA_AUTH_PATH', '/auth/1/');
define('JIRA_API_PATH', '/api/2/');

// Current session. This implementation is NOT ACCEPTABLE. See auth.php.
if ( isset($_COOKIE[session_name()]) ) {
	session_start();

	if ( isset($_SESSION['jira']['url'], $_SESSION['jira']['user'], $_SESSION['jira']['pass']) ) {
		define('JIRA_URL', $_SESSION['jira']['url']);
		define('JIRA_USER', $_SESSION['jira']['user']);
		define('JIRA_PASS', $_SESSION['jira']['pass']);
	}
	else {
		$_SESSION['jira'] = null;
	}
}
