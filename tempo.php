<?php

require 'inc.bootstrap.php';

do_logincheck();

$offset = (int)@$_GET['offset'];

$from = strtotime(($offset - 1) . ' months');
$to = strtotime(($offset - 0) . ' months');
$tempo = jira_get('/rest/tempo-timesheets/3/worklogs', array(
	'dateFrom' => date('Y-m-d', $from),
	'dateTo' => date('Y-m-d', $to),
), $error, $info);

if ( $error ) {
	include 'tpl.header.php';

	if ( $error == 404 ) {
		echo '<p>No <strong>Tempo</strong> in this house...</p>';
	}
	else {
		echo '<pre>';
		var_dump($error);
		print_r($info);
		echo '</pre>';
	}

	include 'tpl.footer.php';
	exit;
}

// Group by date, index by issue
$dated = $issues = $totals = array();
if (isset($tempo[0]->id)) {
	foreach ( $tempo as $i => $worklog ) {
		// Index issues
		$issues[$worklog->issue->key] = $worklog->issue;

		$seconds = (int)$worklog->timeSpentSeconds;
		if ( $seconds > 0 ) {
			$utc = strtotime($worklog->dateStarted);
			$date = date('Y-m-d', $utc);

			// Group by date & issue
			@$dated[$date][$worklog->issue->key] += $seconds;

			@$totals[$date] += $seconds;
		}
	}
}

// Sort by date: newest first
uksort($dated, function($a, $b) {
	return strtotime($b) - strtotime($a);
});

// Sort every date's issues by work time: more first
foreach ($dated as $date => &$workedIssues) {
	arsort($workedIssues, SORT_NUMERIC);
	unset($workedIssues);
}

include 'tpl.header.php';

echo '<h1>Tempo</h1>';
echo '<p>';
echo '<a href="?offset=' . ($offset - 1) . '">&lt;&lt;</a> | ';
echo date(FORMAT_DATE, $from) . ' &mdash; ' . date(FORMAT_DATE, $to);
echo ' (~' . round(array_sum($totals) / 3600) . 'h)';
echo ' | <a href="?offset=' . ($offset + 1) . '">&gt;&gt;</a>';
echo '</p>';

?>
<style>
label[for="showTitles"] {
	color: #326ca6;
	text-decoration: underline;
}
#showTitles {
	position: absolute;
	visibility: hidden;
	z-index: -1;
}

th, td {
	padding: 3px;
	white-space: normal;
}
th {
	text-align: left;
}
th > .total {
	float: right;
	margin-left: 1.5em;
}
td.time {
	text-align: right;
	vertical-align: top;
}

body:not(.show-summaries) tr.hide-summary .issue-summary {
	display: none;
}
</style>

<p><a href="#" class="toggle-summaries">Toggle titles</a></p>
<?php

echo '<div class="table tempo striping">';
echo '<table width="100%">';
foreach ($dated as $date => $workedIssues) {
	$utc = strtotime($date);
	$prettyDate = date('D ' . FORMAT_DATE, $utc);

	$h = floor($totals[$date] / 3600);
	$m = round($totals[$date] / 60 - $h * 60);
	echo '<tr class="new-section"><th colspan="2">';
	echo '<span class="total">' . $h . 'h' . ( $m ? ' ' . $m . 'm' : '' ) . '</span> ';
	echo '<span class="date">' . $prettyDate . '</span>';
	echo '</th></tr>';
	foreach ($workedIssues as $key => $seconds) {
		$issue = $issues[$key];
		$hours = round($seconds / 3600, 2);
		$time = $hours >= 1.0 ? $hours . 'h' : round($hours * 60) . 'm';

		echo '<tr class="hide-summary">';
		echo '<td class="key">';
		echo '<a class="issue-key" href="issue.php?key=' . $key . '">' . html($key) . '</a> ';
		echo '&nbsp; <a href="logwork.php?key=' . $key . '">+</a>';
		echo '<div class="issue-summary">' . html($issue->summary) . '</div>';
		echo '</td>';
		echo '<td class="time actions"><a href="worklogs.php?key=' . $key . '&summary=' . urlencode($issue->summary) . '&date=' . $date . '&user=' . JIRA_USER . '">' . $time . '</a></td>';
		echo '</tr>';
	}

	echo '<tr><td colspan="2"><br></td></tr>';
}
echo '</table>';
echo '</div>';

?>
<script>
$('.toggle-summaries', true).on('click', function(e) {
	document.body.toggleClass('show-summaries');
});

$$('a.issue-key').on('click', function(e) {
	if ( e.ctrlKey || e.metaKey || e.which == 2 ) return;

	e.preventDefault();
	this.ancestor('tr').toggleClass('hide-summary');
});
</script>
<?php

include 'tpl.footer.php';

function jiraDateToUTC( $date ) {
	return strtotime(str_replace('/', ' ', $date));
}
