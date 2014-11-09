<?php

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
	$table .= '<td>' . html(@$worklog->author->displayName ?: @$worklog->author->name ?: '??') . '</td>';
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
echo '<p>' . $hours . 'h ' . round($minutes) . 'm spent on this issue.</p>';

echo $table;
