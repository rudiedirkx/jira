<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = $_GET['key'];

if ( isset($_POST['summary'], $_POST['description'], $_POST['issuetype'], $_POST['priority'], $_POST['comment']) ) {
	$summary = trim($_POST['summary']);
	$description = trim($_POST['description']);
	$issuetype = trim($_POST['issuetype']);
	$priority = trim($_POST['priority']);
	$comment = trim($_POST['comment']);

	$update = [];
	$fields = array(
		'summary' => $summary,
		'description' => $description,
		'issuetype' => ['id' => $issuetype],
		'priority' => ['id' => $priority],
	);
	if ( $comment ) {
		$update['comment'] = [['add' => ['body' => $comment]]];
	}
	$response = jira_put('issue/' . $key, array_filter(compact('fields', 'update')), $error, $info);

	if ( !$error ) {
		return do_redirect('issue', compact('key'));
	}

	echo '<pre>';
	print_r($update);
	print_r($fields);
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

$issue = jira_get('issue/' . $key, array('expand' => 'transitions,renderedFields'), $error, $info);
if ( $issue ) {
	$issue = new Issue((array) $issue);
	$_title = "{$issue->key} {$issue->fields->summary}";
}
else {
	$_title = 'Invalid issue';
}

include 'tpl.header.php';
include 'tpl.epiccolors.php';

if ( !$issue || $error ) {
	echo '<p>Invalid issue...</p>';
	echo '<pre>';
	var_dump($error);
	print_r($info);
	exit;
}

include 'tpl.issueheader.php';

if ( isset($_GET['edit']) ) {
	// Summary
	// Description
	// Issue type
	// Priority

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
	echo '	<p>Summary: <input name="summary" value="' . html(trim($fields->summary)) . '" /></p>';

	echo '	<p>Description: <textarea name="description" rows="20">' . html(trim($fields->description)) . '</textarea><br><button type="button" data-preview="textarea[name=description]">Preview</button></p>';

	echo '	<p>Issue type: <select name="issuetype">' . html_options($issuetypes, $fields->issuetype->id) . '</select></p>';
	echo '	<p>Priority: <select name="priority">' . html_options($priorities, @$fields->priority->id, '?') . '</select></p>';

	echo '	<p>Add comment: <textarea name="comment" rows="4"></textarea><br><button type="button" data-preview="textarea[name=comment]">Preview</button></p>';

	echo '	<p><button>Save</button></p>';
	echo '</form>';

	include 'tpl.footer.php';
	exit;
}

echo '<div class="issue-description markup">' . ( do_remarkup($issue->renderedFields->description) ?: '<em>No description</em>' ) . '</div>';

$customs = array();
foreach ( $user->selected_show_custom_fields as $cfKey ) {
	$cfName = array_search($cfKey, $user->custom_field_ids);
	if ( $value = @$issue->renderedFields->$cfKey ) {
		$customs[$cfName] = print_r($value, 1);
	}
	else if ( $value = @$fields->$cfKey ) {
		$customs[$cfName] = print_r($value, 1);
	}
}

if ( count($customs) ) {
	echo '<h2 class="visiblity-toggle-header open"><a id="custom-fields-toggler" href="#">' . count($customs) . ' custom fields</a></h2>';
	echo '<div class="custom-fields">';
	foreach ($customs as $cfName => $text) {
		echo '<div class="custom-field">';
		echo '<h3 class="custom-field-title">' . html($cfName) . '</h3>';
		echo '<div class="custom-field-value"><pre style="margin: 0">' . html(trim($text)) . '</pre></div>';
		echo '</div>';
	}
	echo '</div>';

	?>
	<script>
	(function(a) {
		// a.parentNode.classList.remove('open');
		a.addEventListener('click', function(e) {
			e.preventDefault();
			var h2 = this.parentNode;
			h2.classList.toggle('open');
		});
	})(document.querySelector('#custom-fields-toggler'));
	</script>
	<?php
}

if ( $issue->self_epic_issues ) {
	echo '<h2>' . count($issue->self_epic_issues) . ' issues in epic</h2>';
	echo '<ol>';
	foreach ( $issue->self_epic_issues as $task ) {
		echo '<li>';
		echo html_icon($task->fields->issuetype, 'issuetype') . ' ';
		echo '<a href="issue.php?key=' . $task->key . '">' . $task->key . '</a> ';
		echo html_icon($task->fields->status, 'status') . ' ';
		echo html(trim($task->fields->summary)) . ' ';
		echo '</li>';
	}
	echo '</ol>';
}

if ( $issue->subtasks ) {
	echo '<h2 class="pre-menu">' . count($issue->subtasks) . ' sub tasks</h2> (<a href="' . $actions['+Subtask'] . '">add</a>)';
	echo '<ol>';
	foreach ( $issue->subtasks as $task ) {
		echo '<li>';
		echo html_icon($task->fields->issuetype, 'issuetype') . ' ';
		echo '<a href="issue.php?key=' . $task->key . '">' . $task->key . '</a> ';
		echo html_icon($task->fields->status, 'status') . ' ';
		echo html(trim($task->fields->summary)) . ' ';
		echo '</li>';
	}
	echo '</ol>';
}

if ( $issue->attachments ) {
	echo '<h2 class="pre-menu">' . count($issue->attachments) . ' attachments</h2> (<a href="' . $actions['Upload'] . '">add</a>)';
	echo '<div class="table attachments">';
	echo '<table border="1">';
	foreach ( $issue->attachments AS $attachment ) {
		$created = strtotime($attachment->created);
		$size = $attachment->size > 1.2e6 ? number_format($attachment->size / 1e6, 2) . ' MB' : number_format($attachment->size / 1e3, 0, '.', '') . ' kB';
		$a = '<a target="_blank" href="attachment.php?id=' . $attachment->id . '">';

		echo '<tr>';
		if ( $user->config('show_thumbnails') ) {
			echo '<td class="thumbnail">';
			if ( !empty($attachment->thumbnail) ) {
				echo $a . '<img data-attachment="' . $attachment->id . '" data-context="thumbnail" data-src="attachment.php?thumbnail&id=' . $attachment->id . '" /></a>';
			}
			echo '</td>';
		}
		echo '<td>' . $a . html($attachment->filename) . '</a></td>';
		echo '<td align="right">' . $size . '</td>';
		echo '<td>' . date(FORMAT_DATETIME, $created) . '</td>';
		echo '<td>' . html($attachment->author->displayName) . '</td>';
		echo '<td><a class="ajax" data-confirm="You sure? Gone is really, really gone. We can\'t restore attachments." href="?key=' . $key . '&delete_attachment=' . $attachment->id . '&token=' . XSRF_TOKEN . '">x</a></td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</div>';
}

if ( $issue->links ) {
	echo '<h2 class="pre-menu">' . count($issue->links) . ' links</h2> (<a href="' . $actions['Link'] . '">add</a>)';
	echo '<div class="table links">';
	echo '<table border="1">';
	foreach ( $issue->links AS $i => $link ) {
		$first = $i == 0;

		$linkedIssue = @$link->outwardIssue ?: $link->inwardIssue;
		$xward = @$link->outwardIssue ? 'outward' : 'inward';
		$linkTitle = $link->type->$xward;

		echo '<tr>';
		if ($first) {
			echo '<td rowspan="' . count($issue->links) . '">This issue</td>';
		}
		echo '<td>' . html($linkTitle) . '</td>';
		echo '<td>' . html_icon($linkedIssue->fields->issuetype, 'issuetype') . ' <a href="issue.php?key=' . $linkedIssue->key . '">' . $linkedIssue->key . '</a> ' . html_icon($linkedIssue->fields->status, 'status') . ' ' . html(trim($linkedIssue->fields->summary)) . '</td>';
		echo '<td><a class="ajax" data-confirm="Unlink this issue from ' . $linkedIssue->key . '? Re-linking is easy." href="?key=' . $key . '&delete_link=' . $link->id . '&token=' . XSRF_TOKEN . '">x</a></td>';
		echo '</tr>';
	}
	echo '</table>';
	echo '</div>';
}

if ( $issue->worklogs->total || !$fields->issuetype->subtask ) {
	$total = '0+';
	if ( $issue->worklogs->total ) {
		$total = $issue->worklogs->total;
		if ( !$fields->issuetype->subtask ) {
			$total .= '+';
		}
	}

	$storypoints = $issue->story_points ? ' (' . $issue->story_points . ' pt)' : '';
	$summary = trim($fields->summary) . $storypoints;
	$allWorklogsUri = 'worklogs.php?key=' . $key . '&subtasks=' . implode(',', $issue->subkeys) . '&summary=' . urlencode($summary);

	echo '<h2 class="pre-menu">' . $total . ' worklogs</h2> (<a href="' . $actions['Log work'] . '">add</a> | <a href="' . $allWorklogsUri . '">see all</a>)';

	$minutes = 0;
	foreach ( $issue->worklogs->worklogs as $worklog ) {
		$minutes += $worklog->timeSpentSeconds / 60;
	}


	// Show link to combined list
	if ( !$minutes ) {
		echo '<p><a href="' . $allWorklogsUri . '">See ALL worklogs, incl subtasks.</a></p>';
	}
	// Summarize and link
	else if ( $issue->subtasks || $fields->worklog->total > count($issue->worklogs->worklogs) ) {
		$guess = $minutes * $fields->worklog->total / count($issue->worklogs->worklogs);
		echo '<p>~ ' . round($guess / 60, 1) . ' hours (<strong>guess</strong>) spent. <a href="worklogs.php?key=' . $key . '&subtasks=' . implode(',', $issue->subkeys) . '&summary=' . urlencode($summary) . '">See ALL worklogs, incl subtasks.</a></p>';
	}
	// Show all logs, there's nothing else
	else {
		$worklogs = $issue->worklogs->worklogs;
		include 'tpl.worklogs.php';
	}
}

echo '<h2 class="pre-menu">' . count($issue->comments) . ' comments</h2> (<a href="#new-comment">add</a>)';
echo '<div class="comments">';
foreach ( $issue->comments AS $i => $comment ) {
	$created = strtotime($comment->created);
	echo '<div id="comment-' . $comment->id . '">';
	echo '<p class="meta">';
	echo '  [<a href="#comment-' . $comment->id . '">' . date(FORMAT_DATETIME, $created) . '</a>]';
	echo '  by <strong style="white-space: nowrap">' . html($comment->author->displayName) . '</strong>';
	echo '  <span style="white-space: nowrap">[ <a href="comment.php?key=' . $key . '&id=' . $comment->id . '&summary=' . urlencode(trim($fields->summary)) . '">e</a> |';
	echo '  <a class="ajax" data-confirm="DELETE this COMMENT for ever and ever?" href="?key=' . $key . '&delete_comment=' . $comment->id . '&token=' . XSRF_TOKEN . '">x</a> ]</span>';
	echo '</p>';
	echo '<div class="comment-body markup">' . do_remarkup($issue->renderedFields->comment->comments[$i]->body) . '</div>';
	echo '</div>';
	echo '<hr>';
}
echo '</div>';

?>

<div id="new-comment" class="post-comment">
	<h2>New comment</h2>
	<form autocomplete="off" method="post">
		<p>
			<textarea name="comment" rows="8"></textarea><br>
			<button type="button" data-preview="textarea[name=comment]">Preview</button>
		</p>
		<p><button>Save</button></p>
	</form>
</div>

<?php

if ( isset($_GET['debug']) ) {
	$issue->clear();
	echo '<pre>' . print_r($issue, 1) . '</pre>';
}

include 'tpl.footer.php';
