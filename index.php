<?php

require 'inc.bootstrap.php';

do_logincheck();

$perPage = 10;

// Default query
$query = 'status != Closed ORDER BY priority DESC, created DESC';
$querySource = '';
$filterOptions = $user->filter_query_options;

// GET query
if ( !empty($_GET['query']) ) {
	$query = $_GET['query'];
	$querySource = isset($filterOptions[$query]) ? 'filter:get' : 'query:get';
}
// GET project
else if ( !empty($_GET['project']) ) {
	$query = 'project = "' . $_GET['project'] . '" AND ' . $query;
	$querySource = 'project:get';
}
// User's query
else if ( $user->index_query ) {
	$query = $user->index_query;
	$querySource = 'query:setting';
}
// User's Jira filter
else if ( $user->index_filter_object ) {
	$query = $user->index_filter_object->jql;
	$querySource = 'filter:setting';
}
// User's project
else if ( $user->index_project ) {
	$query = 'project = "' . $user->index_project . '" AND ' . $query;
	$querySource = 'project:setting';
}

list($activeTab) = explode(':', $querySource);

// Execute
$page = max(0, (int)@$_GET['page']);
$issues = jira_get('search', array('maxResults' => $perPage, 'startAt' => $page * $perPage, 'jql' => $query), $error, $info);

// Ajax callback
if ( isset($_GET['ajax']) ) {
	include 'tpl.issues.php';
	exit;
}

include 'tpl.header.php';

include 'tpl.indextabs.php';

if ( $error ) {
	echo '<pre>';
	print_r($info);
	exit;
}

echo '<div id="content">';
include 'tpl.issues.php';
echo '</div>';

// echo implode('<br>', $jira_requests);
// echo '<pre>';
// print_r($issues);

include 'tpl.footer.php';
