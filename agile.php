<?php

require 'inc.bootstrap.php';

do_logincheck();

$boardId = $user->config('agile_view_id');
$baseParams = array('rapidViewId' => $boardId);

$params = $baseParams;
$board = jira_get('/rest/greenhopper/1.0/xboard/config', $params, $error, $info);
// DEBUG //
// $board = json_decode(file_get_contents('debug-board.json'));
// echo "\n\n\n\n\n" . json_encode($board) . "\n\n\n\n\n";
// DEBUG //
// print_r($board);
// var_dump($error);
// print_r($info);


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
$groupedIssues = array_reduce($plan->issues, function($list, $issue) use ($hideIssues) {
	if (empty($issue->hidden)) {
		$hide = (int)in_array($issue->id, $hideIssues);
		$list[$hide][] = $issue;
	}
	return $list;
}, array(1 => array(), 0 => array()));
// $issues = array_filter($plan->issues, function($issue) use ($hideIssues) {
// 	return !in_array($issue->id, $hideIssues) && empty($issue->hidden);
// });

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

<form id="form-filter" onsubmit="return false">
	<p>
		<select id="filter"><?= html_options(array_reduce($board->currentViewConfig->quickFilters, function($options, $option) {
			return $options + array($option->id => '&nbsp; ' . $option->name);
		}, array()), @$_GET['filter'], '&nbsp; -- All issues --', false) ?></select>
	</p>
</form>

<?php

echo '<table class="longdata">';
foreach ($groupedIssues as $hidden => $issues) {
	$title = $hidden ? 'hidden' : 'unplanned';
	$class = $hidden ? 'hidden hide' : 'unplanned';

	echo '<tbody class="thead ' . $class . '">';
	echo '<tr><th colspan="4"><a href="#">' . count($issues) . ' ' . $title . ' issues</a></th></tr>';
	echo '</tbody>';

	echo '<tbody class="tbody ' . $class . '">';
	foreach ($issues as $issue) {
		$priority = '';
		if (@$issue->priorityUrl) {
			$priority = html_icon($issue, 'priority');
		}

		$epic = '';
		if (@$issue->epic) {
			$epic = '<span class="epic ' . html($issue->epicField->epicColor) . '"><a href="issue.php?key=' . html($issue->epicField->epicKey) . '">' . html($issue->epicField->text) . '</a></span>';
		}

		echo '<tr>';
		// echo '<td class="type" style="background-color: ' . $issue->color . '; color: ' . $issue->color . '">.</td>';
		echo '<td class="key" style="border-left-color: ' . $issue->color . '"><div class="out"><div class="in">' . $issue->key . '</div></div></td>';
		echo '<td class="priority">' . $priority . '</td>';
		echo '<td class="summary wrap"><a href="issue.php?key=' . $issue->key . '">' . ( $issue->summary ?: '???' ) . '</a> ' . $epic . '</td>';
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
$('filter').on('change', function(e) {
	location = '?filter=' + (parseFloat(this.value) || '');
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
