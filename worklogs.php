<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = @$_GET['key'];
$subtasks = array_filter(explode(',', @$_GET['subtasks']));
$summary = @$_GET['summary'];

$worklogs = array();
foreach (array_merge(array($key), $subtasks) as $lkey) {
	$log = jira_get('issue/' . $lkey . '/worklog');
	$worklogs = array_merge($worklogs, $log->worklogs);
}

include 'tpl.header.php';

echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . html($summary) . '</h1>';

echo '<h2 class="pre-menu">' . count($worklogs) . ' worklogs</h2> (<a href="logwork.php?key=' . $key . '&summary=' . urlencode($summary) . '">add</a>)';

include 'tpl.worklogs.php';

include 'tpl.footer.php';
