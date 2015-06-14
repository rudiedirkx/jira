<?php

require 'inc.bootstrap.php';

do_logincheck();

$boardId = $user->config('agile_view_id');
$baseParams = array('rapidViewId' => $boardId);

// echo '<pre>';

// Quick filters (->currentViewConfig->quickFilters) etc
// DEBUG //
$board = jira_get('/rest/greenhopper/1.0/xboard/config', $baseParams, $error, $info);
// $board = unserialize(file_get_contents('debug-board.txt'));
// echo "\n\n\n\n\n" . serialize($board) . "\n\n\n\n\n";
// DEBUG //
// print_r($board);
// var_dump($error);
// print_r($info);


$params = $baseParams;
if ( !empty($_GET['filter']) ) {
	$params += array('activeQuickFilters' => $_GET['filter']);
}

// DEBUG //
$plan = jira_get('/rest/greenhopper/1.0/xboard/plan/backlog/data', $params, $error, $info);
// unset($plan->epicData, $plan->versionData);
// $plan = unserialize(file_get_contents('debug-plan.txt'));
// echo "\n\n\n\n\n" . serialize($plan) . "\n\n\n\n\n";
// DEBUG //
// print_r($plan);
// var_dump($error);
// print_r($info);
// exit;

$activeSprints = array_filter($plan->sprints, function($sprint) {
	return $sprint->state == 'ACTIVE';
});
$activeSprint = reset($activeSprints);
// print_r($activeSprint);

$hideIssues = $activeSprint ? $activeSprint->issuesIds : array();
$issues = array_filter($plan->issues, function($issue) use ($hideIssues) {
	return !in_array($issue->id, $hideIssues) && empty($issue->hidden);
});

include 'tpl.header.php';

include 'tpl.epiccolors.php';

?>
<style>
.longdata tr {
	vertical-align: top;
}
.longdata th,
.longdata td {
	border-bottom: solid 1px #ccc;
	padding: 2px 3px;
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
	border-right: solid 1px #ccc;
}
</style>

<h1>Plan</h1>

<p>
	<? foreach ($board->currentViewConfig->quickFilters as $index => $filter):
		$active = @$_GET['filter'] == $filter->id;
		?>
		<? if ($index): ?> | <? endif ?>
		<a class="<?= $active ? 'active' : '' ?>" href="agile.php?filter=<?= $filter->id ?>"><?= html($filter->name) ?></a>
	<? endforeach ?>
</p>

<?php

echo '<table class="longdata">';
echo '<tr><th colspan="4">' . count($issues) . ' issues</th></tr>';
foreach ($issues as $issue) {
	$priority = '';
	if (@$issue->priorityUrl) {
		$priority = html_icon($issue, 'priority');
	}

	$epic = '';
	if (@$issue->epic) {
		$epic = '<span class="epic ' . html($issue->epicField->epicColor) . '">' . html($issue->epicField->text) . '</span>';
	}

	echo '<tr>';
	// echo '<td class="type" style="background-color: ' . $issue->color . '; color: ' . $issue->color . '">.</td>';
	echo '<td class="key" style="border-left-color: ' . $issue->color . '"><div class="out"><div class="in">' . $issue->key . '</div></div></td>';
	echo '<td class="priority">' . $priority . '</td>';
	echo '<td class="summary wrap"><a href="issue.php?key=' . $issue->key . '">' . ( $issue->summary ?: '???' ) . '</a> ' . $epic . '</td>';
	echo '<td class="sp">' . @$issue->estimateStatistic->statFieldValue->value . '</td>';
	echo '</tr>';
}
echo '</table>' . "\n\n";

if ( isset($_GET['debug']) ) {
	echo '<pre>' . print_r($board, 1) . '</pre>';
	echo '<pre>' . print_r($issues, 1) . '</pre>';
}

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
