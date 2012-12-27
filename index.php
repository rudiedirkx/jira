<?php

require 'inc.bootstrap.php';

do_logincheck();

$perPage = 10;

// GET query
if ( !empty($_GET['query']) ) {
	$query = $_GET['query'];
}
// User's custom query
else if ( $user->index_query ) {
	$query = $user->index_query;
}
// Default query
else {
	// Project
	empty($_GET['project']) && $_GET['project'] = $user->index_project;
	$project = '';
	if ( !empty($_GET['project']) ) {
		$project = 'project = "' . $_GET['project'] . '" AND ';
	}

	$query = $project . 'status != Closed ORDER BY priority DESC, key DESC';
}

$page = max(0, (int)@$_GET['page']);
$issues = jira_get('search', array('maxResults' => $perPage, 'startAt' => $page * $perPage, 'jql' => $query), $error, $info);
// var_dump($issues);
// var_dump($error);
// var_dump($info);

if ( isset($_GET['ajax']) ) {
	include 'tpl.issues.php';
	exit;
}

$filterOptions = $user->filter_query_options;

?>
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style>
* { /*margin: 0; padding: 0;*/ box-sizing: border-box; -webkit-box-sizing: border-box; }
input:not([type="submit"]):not([type="button"]), select { width: 100%; }

.short-meta { text-align: center; }
.short-meta .left { float: left; }
.short-meta .right, .dates .right { float: right; }
.label { display: inline-block; background: #D3EAF1; padding: 1px 5px; border-radius: 4px; }

.tab-links .active { font-weight: bold; }
.tab-pages .tab-page + .tab-page { display: none; }

#pager { text-align: center; }
</style>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>

<nav class="tab-links">
	<a href="#tab-page-project" class="active">Project</a>
	<a href="#tab-page-query">Query</a>
	<a href="#tab-page-filter">Filter</a>
</nav>
<div class="tab-pages">
	<form class="tab-page" id="tab-page-project">
		<p>
			<!-- Project: -->
			<input name="project" value="<?= html(@$_GET['project']) ?>" placeholder="Project" />
			<input type="submit" />
		</p>
	</form>
	<form class="tab-page" id="tab-page-query">
		<p>
			<!-- Query: -->
			<input name="query" value="<?= html(@$query) ?>" />
			<input type="submit" />
		</p>
	</form>
	<form class="tab-page" id="tab-page-filter">
		<p>
			<!-- Filter: -->
			<select name="query"><?= html_options($filterOptions, $query, '-- Filter') ?></select>
			<input type="submit" />
		</p>
	</form>
</div>

<script>
$('.tab-links a').on('click', function(e) {
	e.preventDefault();
	var $this = $(this),
		$links = $this.parent(),
		$pages = $links.next();
	$pages.children().hide().filter($this.attr('href')).show();
	$links.children().removeClass('active');
	$this.addClass('active');
});
</script>

<?php

if ( $error ) {
	echo '<pre>';
	print_r($info);
	exit;
}

echo '<div id="content">';
include 'tpl.issues.php';
echo '</div>';

// echo '<pre>';
// print_r($jira_requests);
// print_r($issues);
