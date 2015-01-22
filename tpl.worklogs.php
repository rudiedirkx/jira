<?php

usort($worklogs, function($a, $b) {
	$diff = strtotime($a->started) - strtotime($b->started);
	if (!$diff) {
		$diff = strtotime($a->created) - strtotime($b->created);
	}
	return $diff;
});

$table = '';
$table .= '<div class="table worklogs">';
$table .= '<table border="1">';
$minutes = 0;
foreach ( $worklogs AS $worklog ) {
	$minutes += $worklog->timeSpentSeconds / 60;
	$started = strtotime($worklog->started);
	$created = strtotime($worklog->created);

	$table .= '<tr>';
	$table .= '<td title="Created: ' . date(FORMAT_DATETIME, $created) . '">' . date(FORMAT_DATE, $started) . '</td>';
	$table .= '<td>' . $worklog->timeSpent . '</td>';
	$table .= '<td>' . html(@$worklog->author->displayName ?: @$worklog->author->name ?: '??') . '</td>';
	$table .= '<td>' . html(@$worklog->comment ?: '') . '</td>';
	$table .= '<td>';
	$table .= '  <a href="logwork.php?key=' . $key . '&summary=' . urlencode($summary) . '&id=' . $worklog->id . '">e</a> |';
	$table .= '  <a class="ajax" data-confirm="DELETE this WORKLOG forever and ever?" href="issue.php?key=' . $key . '&delete_worklog=' . $worklog->id . '&token=' . XSRF_TOKEN . '">x</a>';
	$table .= '</td>';
	$table .= '</tr>';
}
$table .= '</table>';
$table .= '</div>';

$hours = floor($minutes / 60);
$minutes -= $hours * 60;
echo '<p>' . $hours . 'h ' . round($minutes) . 'm spent on this issue.</p>';

echo $table;
