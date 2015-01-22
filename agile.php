<?php

require 'inc.bootstrap.php';

do_logincheck();

$boardId = $user->config('agile_view_id');

// echo '<pre>';

// Quick filters (->currentViewConfig->quickFilters) etc
// $board = jira_get('/rest/greenhopper/1.0/xboard/config', array('rapidView' => $boardId), $error, $info);
// print_r($board);
// var_dump($error);
// print_r($info);


$plan = jira_get('/rest/greenhopper/1.0/xboard/plan/backlog/data', array('rapidViewId' => $boardId), $error, $info);
// unset($plan->epicData, $plan->versionData);
// print_r($plan);
// var_dump($error);
// print_r($info);

$activeSprints = array_filter($plan->sprints, function($sprint) {
	return $sprint->state == 'ACTIVE';
});
$activeSprint = reset($activeSprints);
// print_r($activeSprint);

$hideIssues = $activeSprint ? $activeSprint->issuesIds : array();

include 'tpl.header.php';

?>
<style>
.longdata tr {
	vertical-align: top;
}
.longdata td {
	border-bottom: solid 1px #ccc;
	padding: 2px 3px;
}
.longdata tr:first-child td {
	border-top: solid 1px #ccc;
}
.longdata td:last-child {
	border-right: solid 1px #ccc;
}
.longdata .key {
	border-left: solid 5px black;
	overflow: hidden;
}
.longdata .key .out {
	width: 5em;
	position: relative;
}
.longdata .key .in {
	position: absolute;
	right: 0;
}
.longdata .sp {
	background-color: #eee;
	text-align: center;
	border-left: solid 1px #ccc;
}
</style>

<h1>Plan</h1>

<?php

echo '<table class="longdata">';
foreach ($plan->issues as $issue) {
	if ( in_array($issue->id, $hideIssues) ) continue;

	$priority = '';
	if (@$issue->priorityUrl) {
		$priority = html_icon($issue, 'priority');
	}

	echo '<tr>';
	// echo '<td class="type" style="background-color: ' . $issue->color . '; color: ' . $issue->color . '">.</td>';
	echo '<td class="key" style="border-left-color: ' . $issue->color . '"><div class="out"><div class="in">' . $issue->key . '</div></div></td>';
	echo '<td class="priority">' . $priority . '</td>';
	echo '<td class="summary wrap"><a href="issue.php?key=' . $issue->key . '">' . $issue->summary . '</a></td>';
	echo '<td class="sp">' . @$issue->estimateStatistic->statFieldValue->value . '</td>';
	echo '</tr>';
}
echo '</table>';

echo '<pre>' . print_r(array_slice($plan->issues, 0, 30), 1) . '</pre>';

include 'tpl.footer.php';

exit;

$error = false;
$sprints = $boardId ? jira_get('/rest/greenhopper/1.0/sprintquery/' . $boardId, array(), $error, $info) : array();
if ( !$boardId || !$sprints || $error ) {
	include 'tpl.header.php';

	echo '<p>No <strong>Greenhopper</strong> in this house...</p>';

	if ( $error ) {
		echo '<pre>';
		var_dump($error);
		print_r($info);
		echo '</pre>';
	}

	include 'tpl.footer.php';
	exit;
}

echo '<pre>';

$actives = array_filter($sprints->sprints, function($sprint) {
	return $sprint->state == 'ACTIVE';
});

print_r($actives);

print_r($sprints);
