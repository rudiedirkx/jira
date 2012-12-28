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

// Current session
$user = null;
if ( isset($_COOKIE['JIRA_URL'], $_COOKIE['JIRA_AUTH']) ) {
	$auth = do_decrypt($_COOKIE['JIRA_AUTH']);
	list($username) = explode(':', $auth, 2);

	define('JIRA_URL', $_COOKIE['JIRA_URL']);
	define('JIRA_USER', $username);
	define('JIRA_AUTH', $auth);

	$user = User::get();
}
