<?php

require 'bootstrap.php';

$query = 'project = BR AND status != Closed ORDER BY priority DESC, key DESC';

$issues = jira_get('search', array('maxResults' => 25, 'jql' => $query));

?>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
.short-meta { text-align: center; }
.short-meta .left { float: left; width: 15%; text-align: left; }
.short-meta .right { float: right; width: 45%; text-align: right; }
</style>
<?php

foreach ( $issues->issues AS $issue ) {
	$fields = $issue->fields;

	$resolution = '';
	if ( $fields->resolution ) {
		$resolution = ': ' . $fields->resolution->name;
	}

	$status = $fields->resolution ? $fields->resolution->name : $fields->status->name;

	echo '<h2><a href="issue.php?key=' . $issue->key . '">' . $issue->key . ' ' . $fields->summary . '</a></h2>';
	echo '<p class="short-meta">';
	echo '	<span class="left">' . $fields->issuetype->name . '</span>';
	echo '	<span class="center">' . $fields->priority->name . '</span>';
	echo '	<span class="right">' . $status . '</span>';
	echo '</p>';
	echo '<hr>';
}

// echo '<pre>';
// print_r($issues);
