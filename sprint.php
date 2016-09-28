<?php

require 'inc.bootstrap.php';

do_logincheck();

include 'tpl.header.php';

$boards = $user->agile_boards;
natcasesort($boards);

$agileViewId = @$_GET['board'] ?: $user->config('agile_view_id');

$params = array('rapidViewId' => $agileViewId);
$board = jira_get('/rest/greenhopper/1.0/xboard/config', $params, $error, $info);

$sprints = jira_get("/rest/agile/1.0/board/{$agileViewId}/sprint", array('state' => 'ACTIVE'), $error, $info);
$sprints = array_reduce($sprints->values, function($sprints, $sprint) {
	return $sprints + array($sprint->id => $sprint->name);
}, []);

?>
<h1><?= implode(', ', $sprints) ?></h1>

<form id="form-filter">
	<p>
		<select id="board" name="board"><?= html_options($boards, $agileViewId, '-- Select a board --') ?></select>
	</p>
</form>

<script>
$('form-filter').on('change', function(e) {
	this.submit();
});
</script>

<?php

$columns = array_reduce($board->currentViewConfig->columns, function($list, $column) {
	return $list + array($column->id => $column);
}, []);

$statusToColumn = array_reduce($columns, function($list, $column) {
	return $list += array_combine($column->statusIds, array_fill(0, count($column->statusIds), $column->id));
}, []);

$issues = jira_get('search', array(
	'maxResults' => 200,
	'fields' => 'summary,status,parent,subtasks',
	'jql' => 'sprint IN (' . implode(', ', array_keys($sprints)) . ') AND issuetype in standardIssueTypes() ORDER BY rank',
), $error, $info);
$issues = array_reduce($issues->issues, function($list, $issue) {
	$issue->_subtasksByColumn = [];
	return $list + [$issue->key => $issue];
}, []);

$columnize = function($issue) use (&$columns, &$statusToColumn) {
	$statusId = $issue->fields->status->id;
	if ( !isset($statusToColumn[$statusId]) ) {
		$statusToColumn[$statusId] = 's_' . $statusId;
		$columns['s_' . $statusId] = (object)['name' => $issue->fields->status->name];
	}
	return $statusToColumn[$statusId];
};

$issuesByColumn = [];
foreach ($issues as $issue) {
	// Place parent issue
	$parentColumn = $columnize($issue);
	$issuesByColumn[$parentColumn][] = $issue;
	$parentInColumns = [$parentColumn];

	// Place subtasks, AND parent issue
	foreach ((array) @$issue->fields->subtasks as $subtask) {
		$subColumn = $columnize($subtask);
		$issue->_subtasksByColumn[$subColumn][] = $subtask;

		if ( !in_array($subColumn, $parentInColumns) ) {
			$issuesByColumn[$subColumn][] = $issue;
			$parentInColumns[] = $subColumn;
		}
	}
}

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

<?php

foreach ($columns as $columnId => $column) {
	$issues = (array)@$issuesByColumn[$columnId];

	echo "<details>\n";
	echo '<summary>' . html($column->name) . ' (' . count($issues) . ")</summary>\n";

	echo '<table>';
	foreach ($issues as $issue) {
		echo '<tr>';
		echo '<th nowrap><a href="issue.php?key=' . $issue->key . '" target="_blank">' . html($issue->key) . '</a></th>';
		echo '<td class="wrap">' . html($issue->fields->summary) . '</td>';
		echo "</tr>\n";

		foreach ((array) @$issue->_subtasksByColumn[$columnId] as $subtask) {
			echo '<tr>';
			echo '<th></th>';
			echo '<td class="wrap"><a href="issue.php?key=' . $subtask->key . '" target="_blank">' . html($subtask->key) . '</a> | ' . html($subtask->fields->summary) . '</td>';
			echo "</tr>\n";
		}
	}
	echo "</table>\n";
	echo "</details>\n";
}

include 'tpl.footer.php';
