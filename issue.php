<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = $_GET['key'];

if ( isset($_POST['summary'], $_POST['description'], $_POST['reporter'], $_POST['issuetype'], $_POST['priority'], $_POST['comment']) ) {
	$summary = trim($_POST['summary']);
	$description = trim($_POST['description']);
	$reporter = trim($_POST['reporter']);
	$issuetype = trim($_POST['issuetype']);
	$priority = trim($_POST['priority']);
	$comment = trim($_POST['comment']);

	$update = array(
		'summary' => array(array('set' => $summary)),
		'description' => array(array('set' => $description)),
		'issuetype' => array(array('set' => $issuetype)),
		'priority' => array(array('set' => array('id' => $priority))),
	);
	if ( $reporter ) {
		$update['reporter'] = array(array('set' => array('name' => $reporter)));
	}
	if ( $comment ) {
		$update['comment'] = array(array('add' => array('body' => $comment)));
	}
	$response = jira_put('issue/' . $key, compact('update'), $error, $info);

	if ( !$error ) {
		return do_redirect('issue', compact('key'));
	}

	echo '<pre>';
	print_r($update);
	var_dump($error);
	print_r($response);
	print_r($info);

	exit;
}

else if ( !empty($_POST['comment']) ) {
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

else if ( isset($_GET['delete_attachment']) ) {
	$id = $_GET['delete_attachment'];
	$response = jira_delete('attachment/' . $id, null, $error, $info);

	return do_redirect('issue', compact('key'));
}

else if ( isset($_GET['delete_worklog']) ) {
	$id = $_GET['delete_worklog'];
	$response = jira_delete('issue/' . $key . '/worklog/' . $id, null, $error, $info);

	return do_redirect('issue', compact('key'));
}

else if ( isset($_GET['delete_comment']) ) {
	$id = $_GET['delete_comment'];
	$response = jira_delete('issue/' . $key . '/comment/' . $id, null, $error, $info);

	return do_redirect('issue', compact('key'));
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

$issue = jira_get('issue/' . $key, array('expand' => 'transitions,renderedFields'));
// print_r($issue);
$fields = $issue->fields;
$transitions = $issue->transitions;
$subtasks = !empty($fields->subtasks) ? $fields->subtasks : array();
$parent = @$fields->parent;
$attachments = $fields->attachment;
$worklogs = $fields->worklog->worklogs;
$comments = $fields->comment->comments;

$fieldsmeta = $user->custom_field_ids;

usort($attachments, function($a, $b) {
	return strtotime($a->created) - strtotime($b->created);
});

include 'tpl.header.php';

$actionPath = 'transition.php?key=' . $key . '&assignee=' . urlencode(@$fields->assignee->name) . '&summary=' . urlencode($fields->summary) . '&transition=';

$actions = array();
$actions['Edit'] = 'issue.php?key=' . $key . '&edit';
$actions['Assign'] = $actionPath . 'assign';
foreach ( $transitions AS $transition ) {
	$actions[$transition->name] = $actionPath . $transition->id;
}
$actions['Labels'] = 'labels.php?key=' . $key;
$actions['Log work'] = 'logwork.php?key=' . $key . '&summary=' . urlencode($fields->summary);
$actions['Upload'] = 'upload.php?key=' . $key . '&summary=' . urlencode($fields->summary);

$resolution = '';
if ( $fields->resolution ) {
	$resolution = ': ' . html($fields->resolution->name);
}

$h1Class = $parent ? ' class="with-parent-issue"' : '';
if ( $parent ) {
	echo '<p class="parent-issue">&gt; <a href="issue.php?key=' . $parent->key . '">' . $parent->key . '</a> ' . html($parent->fields->summary) . '</p>';
}
$storypoints = @$fieldsmeta['story points'] && @$fields->{$fieldsmeta['story points']} ? ' (' . @$fields->{$fieldsmeta['story points']} . ' pt)' : '';
echo '<h1' . $h1Class . '><a href="issue.php?key=' . $issue->key . '">' . $issue->key . '</a> ' . html($fields->summary) . $storypoints . '</h1>';
echo '<p class="menu">' . html_links($actions) . '</p>';

echo '<p class="meta">';
echo '	[<img class="icon issuetype" src="' . html($fields->issuetype->iconUrl) . '" alt="' . html($fields->issuetype->name) . '" /> ' . html($fields->issuetype->name) . ' | <img class="icon priority" src="' . html($fields->priority->iconUrl) . '" alt="' . html($fields->priority->name) . '" /> ' . html($fields->priority->name) . '] ';
echo '	by ' . html($fields->reporter->displayName) . ' ';
echo '	on ' . date(FORMAT_DATETIME, strtotime($fields->created)) . ' | ';
echo '	<strong>' . html($fields->status->name) . $resolution . '</strong> | ';
echo '	Assignee: ' . ( html(@$fields->assignee->displayName) ?: '<em>None</em>' ) . ' | ';
if ( $fields->labels ) {
	echo '	Labels: <span class="label">' . implode('</span> <span class="label">', array_map('html', $fields->labels)) . '</span> | ';
}
if ( !empty($fields->watches) ) {
	$watches = $fields->watches->isWatching ? ' active' : '';
	echo '	<a href="issue.php?key=' . $key . '&watch=' . (int)!$watches . '" class="active-state' . $watches . '">★</a> (watch) | ';
}
if ( !empty($fields->votes) ) {
	$voted = $fields->votes->hasVoted ? ' active' : '';
	echo '	<a href="issue.php?key=' . $key . '&vote=' . (int)!$voted . '" class="active-state' .  $voted. '">♥</a> (vote)';
}
echo '</p>';

if ( isset($_GET['edit']) ) {
	// Summary
	// Description
	// Issue type
	// Priority
	// Reporter

	$meta = jira_get('issue/' . $key . '/editmeta', array('expand' => 'projects.issuetypes.fields'), $error, $info);

	$issuetypes = array();
	foreach ( $meta->fields->issuetype->allowedValues AS $issuetype ) {
		$issuetypes[$issuetype->id] = $issuetype->name;
	}

	$priorities = array();
	foreach ( $meta->fields->priority->allowedValues AS $priority ) {
		$priorities[$priority->id] = $priority->name;
	}

	echo '<form action method="post">';
	echo '	<p>Summary: <input name="summary" value="' . html($fields->summary) . '" /></p>';

	echo '	<p>Description: <textarea name="description" rows="8">' . html($fields->description) . '</textarea></p>';

	echo '	<p>Issue type: <select name="issuetype">' . html_options($issuetypes, $fields->issuetype->id) . '</select></p>';
	echo '	<p>Priority: <select name="priority">' . html_options($priorities, $fields->priority->id) . '</select></p>';

	echo '	<p>Reporter (' . $fields->reporter->name . '): <input name="reporter" /></p>';

	echo '	<p>Add comment: <textarea name="comment" rows="4"></textarea></p>';

	echo '	<p><input type="submit" /></p>';
	echo '</form>';

	include 'tpl.footer.php';
	exit;
}

echo '<div class="issue-description markup">' . ( do_remarkup($issue->renderedFields->description) ?: '<em>No description</em>' ) . '</div>';

if ( $subtasks ) {
	echo '<h2>' . count($subtasks) . ' sub tasks</h2>';
	echo '<ol>';
	foreach ( $subtasks as $task ) {
		echo '<li><a href="issue.php?key=' . $task->key . '">' . $task->key . '</a> <img src="' . $task->fields->status->iconUrl . '" alt="' . html($task->fields->status->name) . '" /> ' . html($task->fields->summary) . '</li>';
	}
	echo '</ol>';
}

if ( $attachments ) {
	echo '<h2>' . count($attachments) . ' attachments</h2>';
	echo '<div class="table attachments">';
	echo '<table border="1">';
	foreach ( $attachments AS $attachment ) {
		$created = strtotime($attachment->created);

		echo '<tr>';
		echo '<td><a target="_blank" href="' . html($attachment->content) . '">' . html($attachment->filename) . '</a></td>';
		echo '<td>' . date(FORMAT_DATETIME, $created) . '</td>';
		echo '<td>' . html($attachment->author->displayName) . '</td>';
		echo '<td><a data-confirm="You sure? Gone is really, really gone. We can\'t restore attachments." href="?key=' . $key . '&delete_attachment=' . $attachment->id . '">x</a></td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</div>';
}

if ( $worklogs ) {
	echo '<h2 class="pre-menu">' . count($worklogs) . ' worklogs</h2> (<a href="' . $actions['Log work'] . '">add</a>)';
	echo '<div class="table worklogs">';
	echo '<table border="1">';
	foreach ( $worklogs AS $worklog ) {
		$started = strtotime($worklog->started);

		echo '<tr>';
		echo '<td>' . date(FORMAT_DATETIME, $started) . '</td>';
		echo '<td>' . $worklog->timeSpent . '</td>';
		echo '<td>' . html($worklog->author->displayName) . '</td>';
		echo '<td>' . html(@$worklog->comment ?: '') . '</td>';
		echo '<td>';
		echo '  <a href="logwork.php?key=' . $key . '&summary=' . urlencode($fields->summary) . '&id=' . $worklog->id . '">e</a> |';
		echo '  <a data-confirm="DELETE this WORKLOG forever and ever?" href="?key=' . $key . '&delete_worklog=' . $worklog->id . '">x</a>';
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';
	// echo '<pre>' . print_r($worklogs, 1) . '</pre>';
	echo '</div>';
}

echo '<h2>' . count($comments) . ' comments</h2>';
echo '<div class="comments">';
foreach ( $comments AS $i => $comment ) {
	$created = strtotime($comment->created);
	echo '<div id="comment-' . $comment->id . '">';
	echo '<p class="meta">';
	echo '  [' . date(FORMAT_DATETIME, $created) . ']';
	echo '  by <strong>' . html($comment->author->displayName) . '</strong>';
	echo '  [ <a href="comment.php?key=' . $key . '&id=' . $comment->id . '&summary=' . urlencode($fields->summary) . '">e</a> |';
	echo '  <a data-confirm="DELETE this COMMENT for ever and ever?" href="?key=' . $key . '&delete_comment=' . $comment->id . '">x</a> ]';
	echo '</p>';
	echo '<div class="comment-body markup">' . do_remarkup($issue->renderedFields->comment->comments[$i]->body) . '</div>';
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

include 'tpl.footer.php';
