<?php

require 'bootstrap.php';

$key = $_GET['key'];
$summary = $_GET['summary'];
$action = @$_GET['transition'];
$assignee = @$_GET['assignee'] ?: '?';

if ( isset($_POST['status'], $_POST['comment'], $_POST['assignee']) ) {
	$status = trim($_POST['status']);
	$resolution = trim(@$_POST['resolution']);
	$comment = trim($_POST['comment']);
	$assignee = trim($_POST['assignee']);

	$update = array();
	if ( $comment ) {
		$update['update']['comment']['add']['body'] = $comment;
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

echo '<pre>';
print_r($update);
echo '</pre>';

	$response = jira_post('issue/' . $key . '/transitions', $update, $error, $info);
	echo '<p>error: ' . (int)$error . '</p>';
	echo '<p><a href="?key=' . $key . '">Back</a></p>';
	echo '<pre>';
	print_r($response);
	print_r($info);
	echo '</pre>';
	exit;
}

$transitions = jira_get('issue/' . $key . '/transitions', array('expand' => 'transitions.fields'));

$actions = array('' => '-- No change');
$transitionsById = array();
foreach ( $transitions->transitions AS $transition ) {
	$transitionsById[$transition->id] = $transition;
	$actions[$transition->id] = $transition->name;
}

?>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
textarea { width: 100%; }
</style>
<?php

echo '<p class="menu"><a href="index.php">&lt; index</a></p>';
echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . $summary . '</h1>';

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
echo '<p>Assignee: <input name="assignee" value="" /> (' . $assignee . ')</p>';
echo '<p><input type="submit" /></p>';
echo '</form>';
echo '</div>';

echo '<pre>';
print_r($transitions);
