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

else if ( isset($_GET['delete_link']) ) {
	do_tokencheck();

	$id = $_GET['delete_link'];
	$response = jira_delete('issueLink/' . $id, null, $error, $info);

	exit(IS_AJAX ? 'OK' : do_redirect('issue', compact('key')));
}

else if ( isset($_GET['delete_attachment']) ) {
	do_tokencheck();

	$id = $_GET['delete_attachment'];
	$response = jira_delete('attachment/' . $id, null, $error, $info);

	exit(IS_AJAX ? 'OK' : do_redirect('issue', compact('key')));
}

else if ( isset($_GET['delete_worklog']) ) {
	do_tokencheck();

	$id = $_GET['delete_worklog'];
	$response = jira_delete('issue/' . $key . '/worklog/' . $id, null, $error, $info);

	exit(IS_AJAX ? 'OK' : do_redirect('issue', compact('key')));
}

else if ( isset($_GET['delete_comment']) ) {
	do_tokencheck();

	$id = $_GET['delete_comment'];
	$response = jira_delete('issue/' . $key . '/comment/' . $id, null, $error, $info);

	exit(IS_AJAX ? 'OK' : do_redirect('issue', compact('key')));
}

else if ( isset($_GET['watch']) ) {
	do_tokencheck();

	$method = !empty($_GET['watch']) ? 'jira_post' : 'jira_delete';
	$data = !empty($_GET['watch']) ? JIRA_USER : array('username' => JIRA_USER);
	$response = $method('issue/' . $key . '/watchers', $data, $error, $info);

	exit(IS_AJAX ? 'OK' : do_redirect('issue', compact('key')));
}

else if ( isset($_GET['vote']) ) {
	do_tokencheck();

	$method = !empty($_GET['vote']) ? 'jira_post' : 'jira_delete';
	$response = $method('issue/' . $key . '/votes', null, $error, $info);

	exit(IS_AJAX ? 'OK' : do_redirect('issue', compact('key')));
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
$created = strtotime($fields->created);
$updated = strtotime($fields->updated);
$transitions = $issue->transitions;
$subtasks = !empty($fields->subtasks) ? $fields->subtasks : array();
$subkeys = array_map(function($issue) {
	return $issue->key;
}, $subtasks);
$parent = @$fields->parent;
$attachments = $fields->attachment;
$worklogs = $fields->worklog->worklogs;
$links = $fields->issuelinks;
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
$actions['Link'] = 'link.php?key=' . $key . '&summary=' . urlencode($fields->summary);
$actions['➔ View in Jira'] = JIRA_URL . '/browse/' . $key;

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

$meta = array();
echo '<p class="meta">';
$meta[] = html_icon($fields->issuetype, 'issuetype') . ' ' . html($fields->issuetype->name);
if ($fields->priority) {
	$meta[] = html_icon($fields->priority, 'priority') . ' ' . html($fields->priority->name);
}
$meta[] = html($fields->reporter->displayName) . ' (' . date(FORMAT_DATETIME, $created) . ')';
$meta[] = html_icon($fields->status, 'status') . ' <strong>' . html($fields->status->name) . $resolution . '</strong>';
$meta[] = '<em>' . ( html(@$fields->assignee->displayName) ?: 'No assignee' ) . '</em>';
if ( $fields->labels ) {
	$meta[] = '<span class="label">' . implode('</span> <span class="label">', array_map('html', $fields->labels)) . '</span>';
}
if ( !empty($fields->watches) ) {
	$watches = $fields->watches->isWatching ? 'active' : '';
	$meta[] = '<a href="issue.php?key=' . $key . '&watch=' . (int)!$watches . '&token=' . XSRF_TOKEN . '" class="ajax active-state ' . $watches . '">★</a> (watch)';
}
if ( !empty($fields->votes) ) {
	$voted = $fields->votes->hasVoted ? 'active' : '';
	$meta[] = '<a href="issue.php?key=' . $key . '&vote=' . (int)!$voted . '&token=' . XSRF_TOKEN . '" class="ajax active-state ' .  $voted. '">♥</a> (vote)';
}
if ( $updated && $updated > $created ) {
	$meta[] = 'Updated on ' . date(FORMAT_DATETIME, $updated);
}
echo implode(' | ', $meta);
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

	echo '<form autocomplete="off" action method="post">';
	echo '	<p>Summary: <input name="summary" value="' . html($fields->summary) . '" /></p>';

	echo '	<p>Description: <textarea name="description" rows="20">' . html($fields->description) . '</textarea></p>';

	echo '	<p>Issue type: <select name="issuetype">' . html_options($issuetypes, $fields->issuetype->id) . '</select></p>';
	echo '	<p>Priority: <select name="priority">' . html_options($priorities, @$fields->priority->id, '?') . '</select></p>';

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
		echo '<li>';
		echo html_icon($task->fields->issuetype, 'issuetype') . ' ';
		echo '<a href="issue.php?key=' . $task->key . '">' . $task->key . '</a> ';
		echo html_icon($task->fields->status, 'status') . ' ';
		echo html($task->fields->summary) . ' ';
		// echo '<em>' . ( html(@$task->fields->assignee->displayName) ?: 'No assignee' ) . '</em>';
		echo '</li>';
	}
	echo '</ol>';
}

if ( $attachments ) {
	echo '<h2 class="pre-menu">' . count($attachments) . ' attachments</h2> (<a href="' . $actions['Upload'] . '">add</a>)';
	echo '<div class="table attachments">';
	echo '<table border="1">';
	foreach ( $attachments AS $attachment ) {
		$created = strtotime($attachment->created);
		$size = $attachment->size > 1.2e6 ? number_format($attachment->size / 1e6, 2) . ' MB' : number_format($attachment->size / 1e3, 0, '.', '') . ' kB';

		echo '<tr>';
		echo '<td><a target="_blank" href="attachment.php?id=' . $attachment->id . '">' . html($attachment->filename) . '</a></td>';
		echo '<td align="right">' . $size . '</td>';
		echo '<td>' . date(FORMAT_DATETIME, $created) . '</td>';
		echo '<td>' . html($attachment->author->displayName) . '</td>';
		echo '<td><a class="ajax" data-confirm="You sure? Gone is really, really gone. We can\'t restore attachments." href="?key=' . $key . '&delete_attachment=' . $attachment->id . '&token=' . XSRF_TOKEN . '">x</a></td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</div>';
}

if ( $links ) {
	echo '<h2 class="pre-menu">' . count($links) . ' links</h2> (<a href="' . $actions['Link'] . '">add</a>)';
	echo '<div class="table links">';
	echo '<table border="1">';
	foreach ( $links AS $i => $link ) {
		$first = $i == 0;

		$linkedIssue = @$link->outwardIssue ?: $link->inwardIssue;
		$xward = @$link->outwardIssue ? 'outward' : 'inward';
		$linkTitle = $link->type->$xward;

		echo '<tr>';
		if ($first) {
			echo '<td rowspan="' . count($links) . '">This issue</td>';
		}
		echo '<td>' . html($linkTitle) . '</td>';
		echo '<td>' . html_icon($linkedIssue->fields->issuetype, 'issuetype') . ' <a href="issue.php?key=' . $linkedIssue->key . '">' . $linkedIssue->key . '</a> ' . html_icon($linkedIssue->fields->status, 'status') . ' ' . html($linkedIssue->fields->summary) . '</td>';
		echo '<td><a class="ajax" data-confirm="Unlink this issue from ' . $linkedIssue->key . '? Re-linking is easy." href="?key=' . $key . '&delete_link=' . $link->id . '&token=' . XSRF_TOKEN . '">x</a></td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</div>';
}

if ( $worklogs ) {
	echo '<h2 class="pre-menu">' . $fields->worklog->total . ' worklogs</h2> (<a href="' . $actions['Log work'] . '">add</a>)';

	$minutes = 0;
	foreach ($worklogs as $worklog) {
		$minutes += $worklog->timeSpentSeconds / 60;
	}

	// Summarize and link
	if ( $subtasks || $fields->worklog->total > count($worklogs) ) {
		$guess = $minutes * $fields->worklog->total / count($worklogs);
		echo '<p>~ ' . round($guess / 60, 1) . ' hours (<strong>guess</strong>) spent. <a href="worklogs.php?key=' . $key . '&subtasks=' . implode(',', $subkeys) . '&summary=' . urlencode($fields->summary) . '">See ALL worklogs, incl subtasks.</a></p>';
	}
	// Show all logs, there's nothing else
	else {
		$summary = $fields->summary;
		include 'tpl.worklogs.php';
	}
}
else {
	echo '<h2 class="pre-menu">? worklogs</h2> (<a href="' . $actions['Log work'] . '">add</a>)';
	echo '<p><a href="worklogs.php?key=' . $key . '&subtasks=' . implode(',', $subkeys) . '&summary=' . urlencode($fields->summary) . '">See ALL worklogs, incl subtasks.</a></p>';
}

echo '<h2 class="pre-menu">' . count($comments) . ' comments</h2> (<a href="#new-comment">add</a>)';
echo '<div class="comments">';
foreach ( $comments AS $i => $comment ) {
	$created = strtotime($comment->created);
	echo '<div id="comment-' . $comment->id . '">';
	echo '<p class="meta">';
	echo '  [' . date(FORMAT_DATETIME, $created) . ']';
	echo '  by <strong>' . html($comment->author->displayName) . '</strong>';
	echo '  [ <a href="comment.php?key=' . $key . '&id=' . $comment->id . '&summary=' . urlencode($fields->summary) . '">e</a> |';
	echo '  <a class="ajax" data-confirm="DELETE this COMMENT for ever and ever?" href="?key=' . $key . '&delete_comment=' . $comment->id . '&token=' . XSRF_TOKEN . '">x</a> ]';
	echo '</p>';
	echo '<div class="comment-body markup">' . do_remarkup($issue->renderedFields->comment->comments[$i]->body) . '</div>';
	echo '</div>';
	echo '<hr>';
}
echo '</div>';

echo '<div id="new-comment" class="post-comment">';
echo '<h2>New comment</h2>';
echo '<form autocomplete="off" method="post">';
echo '<p><textarea name="comment" rows="8"></textarea></p>';
echo '<p><input type="submit" /></p>';
echo '</form>';
echo '</div>';

if ( isset($_GET['debug']) ) {
	echo '<pre>' . print_r($issue, 1) . '</pre>';
}

include 'tpl.footer.php';
