<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = $_GET['key'];

if ( !empty($_POST['comment']) ) {
	$response = jira_post('issue/' . $key . '/comment', array('body' => $_POST['comment']), $error, $info);

	if ( !$error ) {
		return do_redirect('issue#comment-' . $response->id, compact('key'));
	}

	echo '<p>error: ' . (int)$error . '</p>';
	echo '<p><a href="issue.php?key=' . $key . '">Back</a></p>';
	echo '<pre>';
	print_r($response);
	print_r($info);
	echo '</pre>';
	exit;
}

else if ( isset($_GET['watch']) ) {
	$method = !empty($_GET['watch']) ? 'jira_post' : 'jira_delete';
	$data = !empty($_GET['watch']) ? JIRA_USER : array('username' => JIRA_USER);
	$response = $method('issue/' . $key . '/watchers', $data, $error, $info);

	return do_redirect('issue', compact('key'));
}

else if ( isset($_GET['vote']) ) {
	$method = !empty($_GET['vote']) ? 'jira_post' : 'jira_delete';
	$response = $method('issue/' . $key . '/votes', null, $error, $info);

	return do_redirect('issue', compact('key'));
}

else if ( isset($_GET['transitions']) ) {
	$transitions = jira_get('issue/' . $key . '/transitions', array('expand' => 'transitions.fields'));

	echo '<pre>';
	print_r($transitions);
	echo '</pre>';
	exit;
}

$issue = jira_get('issue/' . $key, array('expand' => 'transitions'));
$fields = $issue->fields;
$transitions = $issue->transitions;

$attachments = $fields->attachment;
usort($attachments, function($a, $b) {
	return strtotime($a->created) - strtotime($b->created);
});

$actionPath = 'transition.php?key=' . $key . '&assignee=' . $fields->assignee->name . '&summary=' . urlencode($fields->summary) . '&transition=';

$actions = array();
// $actions['Assign'] = 'assign.php?key=' . $key . '&assignee=' . $fields->assignee->name;
foreach ( $transitions AS $transition ) {
	$actions[$transition->name] = $actionPath . $transition->id;
}
$actions['Labels'] = 'labels.php?key=' . $key . '&id=' . $issue->id . '&' . http_build_query(array('labels' => $fields->labels));

?>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
textarea { width: 100%; }
.label { display: inline-block; background: #D3EAF1; padding: 1px 5px; border-radius: 4px; }
.markup { background: #eee; padding: 10px; }
.active-state { color: #bbb; text-decoration: none; }
.active-state.active { color: #000; }
/*.active-state:not(:hover):not(:focus) + label { display: none; }*/
div.table { width: 100%; overflow: auto; }
table { border-spacing: 0; }
td { padding: 2px 5px; white-space: nowrap; }
</style>
<?php

$resolution = '';
if ( $fields->resolution ) {
	$resolution = ': ' . $fields->resolution->name;
}

$watches = $fields->watches->isWatching ? ' active' : '';
$voted = $fields->votes->hasVoted ? ' active' : '';

echo '<p class="menu"><a href="index.php">&lt; index</a></p>';
echo '<h1>' . $issue->key . ' ' . $fields->summary . '</h1>';
echo '<p class="menu">' . html_links($actions) . '</p>';
echo '<p class="meta">';
echo '[<img src="' . $fields->issuetype->iconUrl . '" alt="' . $fields->issuetype->name . '" /> ' . $fields->issuetype->name . ' | <img src="' . $fields->priority->iconUrl . '" alt="' . $fields->priority->name . '" /> ' . $fields->priority->name . '] ';
echo 'by ' . $fields->reporter->displayName . ' ';
echo 'on ' . date(FORMAT_DATETIME, strtotime($fields->created)) . ' | ';
echo '<strong>' . $fields->status->name . $resolution . '</strong> | ';
echo 'Assignee: ' . $fields->assignee->displayName . ' | ';
if ( $fields->labels ) {
	echo 'Labels: <span class="label">' . implode('</span> <span class="label">', $fields->labels) . '</span> | ';
}
echo '<a href="issue.php?key=' . $key . '&watch=' . (int)!$watches . '" class="active-state' . $watches . '">★ (un)watch</a> | <a href="issue.php?key=' . $key . '&vote=' . (int)!$voted . '" class="active-state' .  $voted. '">♥ (un)vote</a>';
echo '</p>';

echo '<div class="issue-description markup">' . nl2br(trim($fields->description)) . '</div>';

if ( $attachments ) {
	echo '<h2>' . count($attachments) . ' attachments</h2>';
	echo '<div class="table attachments">';
	echo '<table border="1">';
	foreach ( $attachments AS $attachment ) {
		$created = strtotime($attachment->created);

		echo '<tr>';
		echo '<td><a target="_blank" href="' . $attachment->content . '">' . $attachment->filename . '</a></td>';
		echo '<td>' . date(FORMAT_DATETIME, $created) . '</td>';
		echo '<td>' . $attachment->author->displayName . '</td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</div>';
}

$comments = $fields->comment->comments;
echo '<h2>' . count($comments) . ' comments</h2>';
echo '<div class="comments">';
foreach ( $comments AS $comment ) {
	$created = strtotime($comment->created);
	echo '<div id="comment-' . $comment->id . '">';
	echo '<p class="meta">';
	echo '[' . date(FORMAT_DATETIME, $created) . '] ';
	echo 'by ' . $comment->author->displayName . ' ';
	echo '[ <a href="comment.php?key=' . $key . '&id=' . $comment->id . '">e</a> | ';
	echo '<a href="comment.php?key=' . $key . '&id=' . $comment->id . '&delete=1">x</a> ]</p>';
	echo '<div class="comment-body markup">' . nl2br(trim($comment->body)) . '</div>';
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
