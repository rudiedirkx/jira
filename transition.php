<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = $_GET['key'];

$summary = $_GET['summary'];
$action = @$_GET['transition'];
$assignee = @$_GET['assignee'] ?: '?';

if ( isset($_POST['status'], $_POST['comment'], $_POST['assignee']) ) {
	$status = trim($_POST['status']);
	$resolution = trim(@$_POST['resolution']);
	$comment = trim($_POST['comment']);
	$assignee = trim($_POST['assignee']);

	// ASSIGN
	if ( $assignee && $status == 'assign' ) {
		// Add comment
		if ( $comment ) {
			$response = jira_post('issue/' . $key . '/comment', array('body' => $comment), $error, $info);
		}
		else {
			$error = false;
		}

		// Only move forward if that worked, since this action requires 2 API calls =(
		if ( !$error ) {
			// Change assignee
			$response = jira_put('issue/' . $key . '/assignee', array('name' => $assignee), $error, $info);
		}
	}
	// TRANSITION
	else {
		$update = array();
		if ( $comment ) {
			$update['update']['comment'][0]['add']['body'] = $comment;
		}
		if ( $assignee ) {
			$update['fields']['assignee']['name'] = $assignee;
		}
		if ( $resolution ) {
			$update['fields']['resolution']['name'] = $resolution;
		}
		if ( $status ) {
			$update['transition']['id'] = $status;
		}

		$response = jira_post('issue/' . $key . '/transitions', $update, $error, $info);
	}

	if ( !$error ) {
		return do_redirect('issue', array('key' => $key));
	}

	echo '<pre>';
	print_r($update);
	var_dump($error);
	print_r($response);
	print_r($info);

	exit;
}

$transitions = jira_get('issue/' . $key . '/transitions', array('expand' => 'transitions.fields'));

$actions = $transitionsById = array();
$actions[''] = '-- No change';
$actions['assign'] = 'Assign';
foreach ( $transitions->transitions AS $transition ) {
	$transitionsById[$transition->id] = $transition;
	$actions[$transition->id] = $transition->name;
}

include 'tpl.header.php';

echo '<p class="menu"><a href="index.php">&lt; index</a></p>';
echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . html($summary) . '</h1>';

echo '<div class="post-comment">';
echo '<h2>Transition</h2>';
echo '<form method="post">';
echo '<p>Action: <select name="status">' . html_options($actions, $action) . '</select></p>';
if ( isset($transitionsById[$action]->fields->resolution) ) {
	$resolutions = array();
	foreach ( $transitionsById[$action]->fields->resolution->allowedValues AS $resolution ) {
		$resolutions[$resolution->name] = $resolution->name;
	}
	echo '<p>Resolution: <select name="resolution">' . html_options($resolutions, 'Fixed') . '</select></p>';
}
echo '<p>Comment:<br><textarea name="comment" rows="8"></textarea></p>';
echo '<p>Assignee (' . $assignee . '): <input name="assignee" value="" /></p>';
echo '<p><input type="submit" /></p>';
echo '</form>';
echo '</div>';

// echo '<pre>';
// print_r($transitions);

include 'tpl.footer.php';
