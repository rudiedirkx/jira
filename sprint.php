<?php

require 'inc.bootstrap.php';

do_logincheck();

include 'tpl.header.php';

$boards = $user->agile_boards;
natcasesort($boards);

?>
<h1>Sprint</h1>

<form id="form-filter">
	<p>
		<select id="board" name="board"><?= html_options($boards, @$_GET['board'], '-- Select a board --') ?></select>
	</p>
</form>

<script>
$('form-filter').on('change', function(e) {
	this.submit();
});
</script>

<?php

if ( !empty($_GET['board']) ) {
	$baseParams = array('rapidViewId' => $_GET['board']);

	$params = $baseParams;
	$board = jira_get('/rest/greenhopper/1.0/xboard/config', $params, $error, $info);

	$params = $baseParams;
	if ( !empty($_GET['filter']) ) {
		$params += array('activeQuickFilters' => $_GET['filter']);
	}
	$plan = jira_get('/rest/greenhopper/1.0/xboard/plan/backlog/data', $params, $error, $info);

	// DEBUG //
	// $board = json_decode(file_get_contents('debug-board.json'));
	// $plan = json_decode(file_get_contents('debug-plan.json'));
	// DEBUG //

	$activeSprint = array_reduce($plan->sprints, function($foo, $sprint) {
		return $sprint->state == 'ACTIVE' ? $sprint : $foo;
	});

	$columns = array_reduce($board->currentViewConfig->columns, function($list, $column) {
		return $list + array($column->id => $column);
	}, array());

	$statusToColumn = array_reduce($columns, function($list, $column) {
		return $list += array_combine($column->statusIds, array_fill(0, count($column->statusIds), $column->id));
	}, array());

	$allIssues = array_filter($plan->issues, function($issue) use ($activeSprint) {
		return empty($issue->hidden) && in_array($issue->id, $activeSprint->issuesIds);
	});

	$issuesByColumn = array_reduce($allIssues, function($list, $issue) use ($statusToColumn) {
		$list[ $statusToColumn[$issue->statusId] ][] = $issue;
		return $list;
	}, array());

	?>
	<style>
	tr + tr > * {
		border-top: solid 1px #aaa;
	}
	tr th {
		text-align: left;
		border-right: solid 1px #aaa;
	}
	</style>

	<h2><?= html($activeSprint->name) ?></h2>

	<?php

	foreach ($columns as $columnId => $column) {
		$issues = (array)@$issuesByColumn[$columnId];

		echo "<details>\n";
		echo '<summary>' . html($column->name) . ' (' . count($issues) . ")</summary>\n";

		echo '<table>';
		foreach ($issues as $issue) {
			echo '<tr>';
			echo '<th>' . html($issue->key) . '</th>';
			echo '<td class="wrap">' . html($issue->summary) . '</td>';
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "</details>\n";
	}
}

include 'tpl.footer.php';
