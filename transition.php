<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = $_GET['key'];

$summary = $_GET['summary'];
$action = @$_GET['transition'];

if ( isset($_POST['status'], $_POST['comment']) ) {
	$status = trim($_POST['status']);
	$resolution = trim(@$_POST['resolution']);
	$comment = trim($_POST['comment']);

	$update = array();

	if ( $comment ) {
		$update['update']['comment'][0]['add']['body'] = $comment;
	}
	if ( $resolution ) {
		$update['fields']['resolution']['name'] = $resolution;
	}
	if ( $status ) {
		$update['transition']['id'] = $status;
	}

	$response = jira_post('issue/' . $key . '/transitions', $update, $error, $info);

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
// print_r($transitions);

$actions = $transitionsById = array();
$actions[''] = '-- No change';
foreach ( $transitions->transitions AS $transition ) {
	$transitionsById[$transition->id] = $transition;
	$actions[$transition->id] = $transition->name;
}

$_title = "Transition $key";
include 'tpl.header.php';

echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . html($summary) . '</h1>';

echo '<div class="post-comment">';
echo '<h2>' . ( @$actions[$action] ?: 'Transition' ) . '</h2>';
echo '<form autocomplete="off" method="post">';
echo '<p>Action: <select required name="status">' . html_options($actions, $action) . '</select></p>';
if ( isset($transitionsById[$action]->fields->resolution) ) {
	$issue = jira_get('issue/' . $key, array(), $error, $info);
	// print_r($issue);

	$resolutions = array();
	foreach ( $transitionsById[$action]->fields->resolution->allowedValues AS $resolution ) {
		$resolutions[$resolution->name] = $resolution->name;
	}

	echo '<p>Resolution: <select name="resolution">' . html_options($resolutions, @$issue->fields->resolution->name ?: 'Fixed') . '</select></p>';
}
echo '<p>Comment:<br><textarea name="comment" rows="8"></textarea><br><button type="button" data-preview="textarea[name=comment]">Preview</button></p>';

echo '<p><button>Save</button></p>';
echo '</form>';
echo '</div>';

// echo '<pre>';
// print_r($transitions);

include 'tpl.footer.php';
