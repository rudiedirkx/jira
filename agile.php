<?php

require 'inc.bootstrap.php';

do_logincheck();

$boards = $user->agile_boards;

$boardId = @$_GET['board'] ?: $user->config('agile_view_id');
$baseParams = array('rapidViewId' => $boardId);

if ( !$boardId || !isset($boards[$boardId]) ) {
	include 'tpl.header.php';
	?>
	<h1>Plan</h1>
	<form method="get" action="">
		<p><select name="board"><?= html_options($boards, $boardId, '-- Select a board --') ?></select></p>
		<p><button>Open</button></p>
	</form>
	<?php
	exit;
}

$params = $baseParams;
$board = jira_get('/rest/greenhopper/1.0/xboard/config', $params, $error, $info);
// DEBUG //
// $board = json_decode(file_get_contents('debug-board.json'));
// echo "\n\n\n\n\n" . json_encode($board) . "\n\n\n\n\n";
// DEBUG //
// echo '<pre>';
// print_r($board);
// var_dump($error);
// print_r($info);
// exit;


$params = $baseParams;
if ( !empty($_GET['filter']) ) {
	$params += array('activeQuickFilters' => $_GET['filter']);
}
$plan = jira_get('/rest/greenhopper/1.0/xboard/plan/backlog/data', $params, $error, $info);
// DEBUG //
// $plan = json_decode(file_get_contents('debug-plan.json'));
// echo "\n\n\n\n\n" . json_encode($plan) . "\n\n\n\n\n";
// exit;
// DEBUG //
// echo '<pre>';
// print_r($plan);
// var_dump($error);
// print_r($info);
// exit;

$allSprints = array_reduce($plan->sprints, function($sprints, $sprint) {
	$sprints[$sprint->id] = $sprint;
	return $sprints;
}, array());

$allIssues = array_reduce($plan->issues, function($issues, $issue) {
	empty($issue->hidden) and $issues[$issue->id] = $issue;
	return $issues;
}, array());

$groupedIssues = array();
foreach ($plan->sprints as $sprint) {
	foreach ($sprint->issuesIds as $id) {
		if ( isset($allIssues[$id]) ) {
			$groupedIssues[$sprint->id][] = $allIssues[$id];
			unset($allIssues[$id]);
		}
	}
}
$groupedIssues['backlog'] = array_values($allIssues);

// echo '<pre>';
// print_r($groupedIssues);
// exit;

include 'tpl.header.php';

include 'tpl.epiccolors.php';

?>
<style>
#filter {
	height: 40px;
	height: calc(1.4em + 20px);
}

.longdata {
	width: 100%;
}

.longdata .thead th {
	padding: 0;
}
.longdata .thead a {
	display: block;
	padding: 4px 0;
	background-color: #ccc;
	text-decoration: none;
	border-bottom: solid 1px black;
}
.longdata .thead.hide a {
	color: #c00;
}
.longdata .tbody.hide {
	display: none;
}

.longdata tr {
	vertical-align: top;
}
.longdata tr:nth-child(even) {
	background-color: #eee;
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
	border-left: solid 2px #ccc;
	border-right: solid 1px #ccc;
}
.longdata tr:nth-child(even) .sp {
	background-color: #ddd;
}
</style>

<h1>Plan</h1>

<form id="form-filter">
	<p>
		<select name="board"><?= html_options($boards, $boardId, '-- Select a board --') ?></select>
	</p>
	<p>
		<select name="filter"><?= html_options(array_reduce($board->currentViewConfig->quickFilters, function($options, $option) {
			return $options + array($option->id => '' . $option->name);
		}, array()), @$_GET['filter'], '-- All issues --', false) ?></select>
	</p>
</form>

<?php

echo '<table class="longdata">';
foreach ($groupedIssues as $sprintId => $issues) {
	$sprint = @$allSprints[$sprintId];

	$title = $sprint ? $sprint->name . ' (' . $sprint->state . ')' : 'BACKLOG';
	$class = !$sprint || $sprint->state != 'ACTIVE' ? 'hidden hide' : 'unplanned';

	echo '<tbody class="thead ' . $class . '">';
	echo '<tr><th colspan="4"><a href="#">' . $title . ' -- ' . count($issues) . ' issues</a></th></tr>';
	echo '</tbody>';

	echo '<tbody class="tbody ' . $class . '">';
	foreach ($issues as $issue) {
		$priority = '';
		if (@$issue->priorityUrl) {
			$priority = html_icon($issue, 'priority');
		}

		$epic = '';
		if (@$issue->epic) {
			$epic = '<span class="epic ' . html(@$issue->epicField->epicColor) . '"><a href="issue.php?key=' . html($issue->epicField->epicKey) . '">' . html($issue->epicField->text) . '</a></span>';
		}

		echo '<tr>';
		// echo '<td class="type" style="background-color: ' . $issue->color . '; color: ' . $issue->color . '">.</td>';
		echo '<td class="key" style="border-left-color: ' . @$issue->color . '"><div class="out"><div class="in">' . $issue->key . '</div></div></td>';
		echo '<td class="priority">' . $priority . '</td>';
		echo '<td class="summary wrap"><a href="issue.php?key=' . $issue->key . '">' . ( @$issue->summary ?: '???' ) . '</a> ' . $epic . '</td>';
		echo '<td class="sp">' . @$issue->estimateStatistic->statFieldValue->value . '</td>';
		echo '</tr>';
	}
	echo '</tbody>';
}
echo '</table>' . "\n\n";

?>
<script>
// Reset SELECT
setTimeout(function() {
	$('form-filter').reset();
}, 1);

// Change page after filter selection
$('form-filter').on('change', function(e) {
	this.submit();
});

// Toggle issues tbody
$$('.thead a').on('click', function(e) {
	e.preventDefault();
	var thead = this.ancestor('tbody');
	var tbody = thead.nextElementSibling;
	var hidden = tbody.classList.toggle('hide'); // returns bool
	console.log(hidden);
	thead.toggleClass('hide', hidden);
});
</script>
<?php

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
