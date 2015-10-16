<?php

$fields = $issue->fields;

$actionPath = 'transition.php?key=' . $key . '&summary=' . urlencode($fields->summary) . '&transition=';

$actions = array();
$actions['Edit'] = 'issue.php?key=' . $key . '&edit';
$actions['Assign'] = $actionPath . 'assign';
foreach ( $issue->transitions AS $transition ) {
	$actions[$transition->name] = $actionPath . $transition->id;
}
$actions['Labels'] = 'labels.php?key=' . $key;
$actions['Log work'] = 'logwork.php?key=' . $key . '&summary=' . urlencode($fields->summary);
$actions['Upload'] = 'upload.php?key=' . $key . '&summary=' . urlencode($fields->summary);
$actions['Link'] = 'link.php?key=' . $key . '&summary=' . urlencode($fields->summary);
if ( !$fields->issuetype->subtask ) {
	$actions['+Subtask'] = 'new.php?project=' . $fields->project->id . '&parent=' . $key . '&parentsummary=' . urlencode($fields->summary);
}
$actions['Changelog'] = 'changelog.php?key=' . $key;
$actions['➔ View in Jira'] = JIRA_URL . '/browse/' . $key;

$resolution = '';
if ( $fields->resolution ) {
	$resolution = ': ' . html($fields->resolution->name);
}

$h1Class = $issue->parent || $issue->parent_epic_key ? ' class="with-parent-issue"' : '';
if ( $issue->parent_epic_key ) {
	if ( $issue->parent_epic ) {
		echo '<p class="parent-epic">&gt; <span class="epic ' . html($issue->parent_epic->self_epic->color) . '"><a href="issue.php?key=' . $issue->parent_epic_key . '">' . html(trim($issue->parent_epic->self_epic->name)) . '</a></span> ' . html($issue->parent_epic->fields->summary) . '</p>';
	}
	else {
		echo '<p class="parent-epic">&gt; <span class="epic"><a href="issue.php?key=' . $issue->parent_epic_key . '">EPIC</a></span> ' . html($issue->parent_epic_key) . '</p>';
	}
}
else if ( $issue->parent ) {
	echo '<p class="parent-issue">&gt; <a href="issue.php?key=' . $issue->parent->key . '">' . $issue->parent->key . '</a> ' . html($issue->parent->fields->summary) . '</p>';
}
$storypoints = $issue->story_points ? ' (' . $issue->story_points . ' pt)' : '';
echo '<h1' . $h1Class . '><a href="issue.php?key=' . $issue->key . '">' . $issue->key . '</a> ' . html($fields->summary) . $storypoints . '</h1>';
echo '<p class="menu">' . html_links($actions) . '</p>' . "\n";

$meta = array();
echo '<p class="meta">';
$meta[] = html_icon($fields->issuetype, 'issuetype') . ' ' . html($fields->issuetype->name) . ' ' . $issue->self_epic_label;
if ($fields->priority) {
	$meta[] = html_icon($fields->priority, 'priority') . ' ' . html($fields->priority->name);
}
$meta[] = html($fields->reporter->displayName) . ' (' . date(FORMAT_DATETIME, $issue->created) . ')';
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
if ( $issue->updated && $issue->updated > $issue->created ) {
	$meta[] = 'Updated on ' . date(FORMAT_DATETIME, $issue->updated);
}
echo implode(' | ', $meta);
echo '</p>' . "\n";
