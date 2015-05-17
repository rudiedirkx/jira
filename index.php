<?php

require 'inc.bootstrap.php';

do_logincheck();

$perPage = $user->config('index_page_size');

// Default query
$query = 'status != Closed ORDER BY priority DESC, created DESC';
$querySource = '';
$filterOptions = $user->filter_query_options;

// GET search
if ( !empty($_GET['search']) ) {
	$query = "text ~ '" . addslashes(trim($_GET['search'])) . "' ORDER BY updated DESC";
	$querySource = 'search';
}
// GET query
else if ( !empty($_GET['query']) ) {
	$query = trim($_GET['query']);
	$querySource = isset($filterOptions[$query]) ? 'filter:get' : 'query:get';
}
// GET project
else if ( !empty($_GET['project']) ) {
	$query = 'project = "' . addslashes(trim($_GET['project'])) . '" AND ' . $query;
	$querySource = 'project:get';
}
// User's query
else if ( $user->index_query ) {
	$query = $user->index_query;
	$querySource = 'query:setting';
}
// User's Jira filter
else if ( $user->index_filter_object ) {
	$query = $user->index_filter_object->jql;
	$querySource = 'filter:setting';
}
// User's project
else if ( $user->index_project ) {
	$query = 'project = "' . $user->index_project . '" AND ' . $query;
	$querySource = 'project:setting';
}

list($activeTab) = explode(':', $querySource);

// Execute
$page = max(0, (int)@$_GET['page']);
$issues = jira_get('search', array('maxResults' => $perPage, 'startAt' => $page * $perPage, 'jql' => $query), $error, $info);

// Ajax callback
if ( IS_AJAX ) {
	include 'tpl.issues.php';
	exit;
}

$index = true;
include 'tpl.header.php';

include 'tpl.indextabs.php';

if ( $error ) {
	echo '<pre>';
	print_r($info);
	exit;
}

echo '<div id="content">';
include 'tpl.issues.php';
echo '</div>';

?>
<script>
var $content = $('content');
var pages = {};
pages[location.href] = $content.getHTML();

function loadPage(href, push) {
	document.body.addClass('loading');
	$.get(href).on('done', function(e, t) {
		$content.setHTML(t);
		if (push) {
			history.pushState({}, '', href);
		}
		pages[location.href] = $content.getHTML();

		setTimeout(function() {
			if (push) {
				$content.scrollIntoView();
			}
			document.body.removeClass('loading');
		}, 100);
	});
}

window.on('popstate', function(e) {
	if (pages[location.href]) {
		$content.setHTML(pages[location.href]);
	}
	else {
		loadPage(location.href, false);
	}
});

$('content').on('click', '#pager a', function(e) {
	e.preventDefault();

	if (pages[this.href]) {
		$content.setHTML(pages[this.href]);
		$content.scrollIntoView();
	}
	else {
		loadPage(this.href, true);
	}
});
</script>
<?php

// echo implode('<br>', $jira_requests);
// echo '<pre>';
// print_r($issues);

include 'tpl.footer.php';
