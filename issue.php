<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = $_GET['key'];

if ( !empty($_POST['comment']) ) {
	$response = jira_post('issue/' . $key . '/comment', array('body' => $_POST['comment']), $error, $info);

	if ( !$error ) {
		return do_redirect('issue#comment-' . $response->id, array('key' => $key));
	}

	echo '<p>error: ' . (int)$error . '</p>';
	echo '<p><a href="issue.php?key=' . $key . '">Back</a></p>';
	echo '<pre>';
	print_r($response);
	print_r($info);
	echo '</pre>';
	exit;
}

if ( isset($_GET['transitions']) ) {
	$transitions = jira_get('issue/' . $key . '/transitions', array('expand' => 'transitions.fields'));

	echo '<pre>';
	print_r($transitions);
	echo '</pre>';
	exit;
}

$issue = jira_get('issue/' . $key, array('expand' => 'transitions'));
$fields = $issue->fields;
$transitions = $issue->transitions;

$actionPath = 'transition.php?key=' . $key . '&assignee=' . $fields->assignee->name . '&summary=' . urlencode($fields->summary) . '&transition=';

$actions = array();
// $actions['Assign'] = 'assign.php?key=' . $key . '&assignee=' . $fields->assignee->name;
foreach ( $transitions AS $transition ) {
	$actions[$transition->name] = $actionPath . $transition->id;
}
$actions['Labels'] = 'labels.php?key=' . $key . '&' . http_build_query(array('labels' => $fields->labels));

?>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
textarea { width: 100%; }
.label { display: inline-block; background: #D3EAF1; padding: 1px 5px; border-radius: 4px; }
</style>
<?php

$resolution = '';
if ( $fields->resolution ) {
	$resolution = ': ' . $fields->resolution->name;
}

echo '<p class="menu"><a href="index.php">&lt; index</a></p>';
echo '<h1>' . $issue->key . ' ' . $fields->summary . '</h1>';
echo '<p class="menu">' . html_links($actions) . '</p>';
echo '<p class="meta">';
echo '[' . $fields->issuetype->name . ' | ' . $fields->priority->name . '] ';
echo 'by ' . $fields->reporter->displayName . ' | ';
echo $fields->status->name . $resolution . ' | ';
echo 'Assignee: ' . $fields->assignee->displayName . ' | ';
if ( $fields->labels ) {
	echo 'Labels: <span class="label">' . implode('</span> <span class="label">', $fields->labels) . '</span>';
}
echo '</p>';

echo '<div class="description">' . nl2br(trim($fields->description)) . '</div>';

$comments = $fields->comment->comments;
echo '<h2>' . count($comments) . ' comments</h2>';
echo '<div class="comments">';
foreach ( $comments AS $comment ) {
	$created = strtotime($comment->created);
	echo '<div id="comment-' . $comment->id . '">';
	echo '<p class="meta">[' . date('d-M-Y H:i', $created) . '] by ' . $comment->author->displayName . '</p>';
	echo '<div class="description">' . nl2br(trim($comment->body)) . '</div>';
	echo '</div>';
	echo '<hr>';
}
echo '</div>';

echo '<div class="post-comment">';
echo '<h2>New comment</h2>';
echo '<form method="post">';
echo '<p><textarea name="comment" rows="8"></textarea></p>';
echo '<p><input type="submit" /></p>';
echo '</form>';
echo '</div>';

// echo '<pre>';
// print_r($issue);
