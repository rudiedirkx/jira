<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = @$_GET['key'];
$subtasks = array_filter(explode(',', @$_GET['subtasks']));
$summary = @$_GET['summary'];

$worklogs = array();
foreach (array_merge(array($key), $subtasks) as $lkey) {
	$log = jira_get('issue/' . $lkey . '/worklog');
	$worklogs = array_merge($worklogs, $log->worklogs);
}

include 'tpl.header.php';

echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . html($summary) . '</h1>';

echo '<h2 class="pre-menu">' . count($worklogs) . ' worklogs</h2> (<a href="logwork.php?key=' . $key . '&summary=' . urlencode($summary) . '">add</a>)';

$table = '';
$table .= '<div class="table worklogs">';
$table .= '<table border="1">';
$minutes = 0;
foreach ( $worklogs AS $worklog ) {
	$minutes += $worklog->timeSpentSeconds / 60;
	$started = strtotime($worklog->started);

	$table .= '<tr>';
	$table .= '<td>' . date(FORMAT_DATETIME, $started) . '</td>';
	$table .= '<td>' . $worklog->timeSpent . '</td>';
	$table .= '<td>' . html($worklog->author->displayName) . '</td>';
	$table .= '<td>' . html(@$worklog->comment ?: '') . '</td>';
	$table .= '<td>';
	$table .= '  <a href="logwork.php?key=' . $key . '&summary=' . urlencode($summary) . '&id=' . $worklog->id . '">e</a> |';
	$table .= '  <a data-confirm="DELETE this WORKLOG forever and ever?" href="?key=' . $key . '&delete_worklog=' . $worklog->id . '">x</a>';
	$table .= '</td>';
	$table .= '</tr>';
}
$table .= '</table>';
$table .= '</div>';

$hours = floor($minutes / 60);
$minutes -= $hours * 60;
echo '<p>' . $hours . 'h ' . $minutes . 'm spent on this issue.</p>';

echo $table;

include 'tpl.footer.php';
