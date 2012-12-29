<?php

require 'inc.bootstrap.php';

do_logincheck();

$perPage = 10;

// GET query
if ( !empty($_GET['query']) ) {
	$query = $_GET['query'];
}
// User's custom query
else if ( $user->index_query ) {
	$query = $user->index_query;
}
// Default query
else {
	// Project
	empty($_GET['project']) && $_GET['project'] = $user->index_project;
	$project = '';
	if ( !empty($_GET['project']) ) {
		$project = 'project = "' . $_GET['project'] . '" AND ';
	}

	$query = $project . 'status != Closed ORDER BY priority DESC, key DESC';
}

$page = max(0, (int)@$_GET['page']);
$issues = jira_get('search', array('maxResults' => $perPage, 'startAt' => $page * $perPage, 'jql' => $query), $error, $info);
// var_dump($issues);
// var_dump($error);
// var_dump($info);

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
