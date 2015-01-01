<?php

require 'inc.bootstrap.php';

do_logincheck();

// @todo Fetch the last 30 days, not current month (standard)
$tempo = jira_get('/rest/tempo-timesheets/1/user/issues/ASS', array(
	'username' => JIRA_USER,
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
if (isset($tempo->timesheetLines[0]->key)) {
	foreach ( $tempo->timesheetLines as $i => $line ) {
		// Index by issue
		if ( !isset($issues[$line->key]) ) {
			$issues[$line->key] = clone $line;
			unset($issues[$line->key]->workedHours, $issues[$line->key]->date);
		}

		// Group by date
		foreach ( $line->workedHours as $di => $seconds ) {
			if ( $seconds > 0 ) {
				$date = $line->date[$di];
				$hours = round($seconds / 3600, 2);
				$dated[$date][$line->key] = $hours;
				@$totals[$date] += $hours;
			}
		}
	}
}

// Sort by date: newest first
uksort($dated, function($a, $b) {
	return jiraDateToUTC($b) - jiraDateToUTC($a);
});

// Sort every date's issues by work time: more first
foreach ($dated as $date => &$issues) {
	arsort($issues, SORT_NUMERIC);
	unset($issues);
}

include 'tpl.header.php';

echo '<h1>Tempo</h1>';

?>
<style>
th, td {
	padding: 3px;
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
}
</style>
<?php

echo '<table>';
foreach ($dated as $date => $issues) {
	$utc = jiraDateToUTC($date);
	$prettyDate = date('D ' . FORMAT_DATE, $utc);

	$h = floor($totals[$date]);
	$m = round($totals[$date] * 60 - $h * 60);
	echo '<tr><th colspan="2">';
	echo '<span class="total">' . $h . 'h' . ( $m ? ' ' . $m . 'm' : '' ) . '</span> ';
	echo '<span class="date">' . $prettyDate . '</span>';
	echo '</th></tr>';
	foreach ($issues as $key => $hours) {
		$time = $hours >= 1.0 ? $hours . 'h' : round($hours * 60) . 'm';
		echo '<tr><td class="key">' . $key . '</td><td class="time">' . $time . '</td></tr>';
	}

	echo '<tr><td colspan="2"><br></td></tr>';
}
echo '</table>';

include 'tpl.footer.php';

function jiraDateToUTC( $date ) {
	return strtotime(str_replace('/', ' ', $date));
}
