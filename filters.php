<?php

require 'inc.bootstrap.php';

do_logincheck();

if ( isset($_POST['index_filter']) ) {
	$user->save(array('index_filter' => $_POST['index_filter']));
	return do_redirect('filters');
}

$user->unsync();

echo '<p class="menu"><a href="index.php">&lt; index</a></p>';
echo '<h1>Your favorite filters</h1>';

?>
<form action method="post">
	<p>Index filter: <select name="index_filter"><option>-- None<?= html_options($user->filter_options, $user->index_filter) ?></select></p>
	<p><input type="submit" /></p>
</form>
<?php

echo '<pre>';

print_r($user->filters);
print_r($user->filter_options);
print_r($user->filter_query_options);

echo implode("\n", $jira_requests) . "\n";
