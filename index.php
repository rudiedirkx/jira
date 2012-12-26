<?php

require 'inc.bootstrap.php';

do_logincheck();

// GET query
if ( !empty($_GET['query']) ) {
	$query = $_GET['query'];
}
// User's custom query
else if ( $user && $user->index_query ) {
	$query = $user->index_query;
}
// Default query
else {
	// Project
	empty($_GET['project']) && $user && $_GET['project'] = $user->index_project;
	$project = '';
	if ( !empty($_GET['project']) ) {
		$project = 'project = "' . $_GET['project'] . '" AND ';
	}

	$query = $project . 'status != Closed ORDER BY priority DESC, key DESC';
}

?>
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
.short-meta { text-align: center; }
.short-meta .left { float: left; }
.short-meta .right { float: right; }
.label { display: inline-block; background: #D3EAF1; padding: 1px 5px; border-radius: 4px; }
</style>

<form>
	<p>
		Project:
		<input name="project" value="<?= html(@$_GET['project']) ?>" />
		<input type="submit" />
	</p>
</form>

<form>
	<p>
		Query:
		<input name="query" value="<?= html(@$query) ?>" />
		<input type="submit" />
	</p>
</form>
<?php

$issues = jira_get('search', array('maxResults' => 25, 'jql' => $query), $error, $info);
// var_dump($issues);
// var_dump($error);
// var_dump($info);
if ( $error ) {
	echo '<pre>';
	print_r($info);
	exit;
}

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
	echo '	<span class="center">' . ( $fields->priority ? $fields->priority->name : '&nbsp;' ) . '</span>';
	echo '	<span class="right">' . $status . '</span>';
	echo '</p>';
	if ( $fields->labels ) {
		echo '<p class="labels">Labels: <span class="label">' . implode('</span> <span class="label">', $fields->labels) . '</span></p>';
	}
	echo '<hr>';
}

// echo '<pre>';
// print_r($issues);
