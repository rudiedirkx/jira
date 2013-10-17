<?php

require 'inc.bootstrap.php';

do_logincheck();

if ( isset($_POST['index_filter'], $_POST['index_query'], $_POST['index_project']) ) {
	$user->save(array(
		'index_filter' => trim($_POST['index_filter']),
		'index_query' => trim($_POST['index_query']),
		'index_project' => trim($_POST['index_project']),
	));
	return do_redirect('index');
}

else if ( !empty($_POST['new_name']) && !empty($_POST['new_jql']) ) {
	$response = jira_post('filter', array(
		'name' => trim($_POST['new_name']),
		'jql' => trim($_POST['new_jql']),
		'favourite' => 1,
	), $error, $info);

	if ( !$error ) {
		return do_redirect('filters');
	}

	echo '<p>error: ' . (int)$error . '</p>';
	echo '<p><a href="issue.php?key=' . $key . '">Back</a></p>';
	echo '<pre>';
	print_r($response);
	print_r($info);
	echo '</pre>';

	exit;
}

$user->unsync();

include 'tpl.header.php';

echo '<h1>Your filters</h1>';

?>
<form action method="post">
	<p>Used if present, in order:</p>

	<p>1. Index filter: <select name="index_filter"><option>-- None<?= html_options($user->filter_options, $user->index_filter) ?></select></p>
	<p>2. Index query: <input name="index_query" value="<?= html($user->index_query) ?>" /></p>
	<p>3. Index project: <input name="index_project" value="<?= html($user->index_project) ?>" /></p>

	<p><input type="submit" /></p>
</form>

<h2>Create filter</h2>

<form action method="post">
	<p>Name: <input name="new_name" /></p>
	<p>Query: <input name="new_jql" /></p>

	<p><input type="submit" /></p>
</form>
<?php

echo '<pre>';
print_r($user->filters);
print_r($user->filter_options);
print_r($user->filter_query_options);
echo '</pre>';

include 'tpl.footer.php';
