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

else if ( isset($_POST['id'], $_POST['name'], $_POST['jql']) ) {
	$id = $_POST['id'];
	$name = $_POST['name'];
	$jql = $_POST['jql'];

	if ( !$jql || ( !$id && !$name ) ) {
		exit('Missing input');
	}

	// Update
	if ( $id ) {
		$update = array(
			'jql' => trim($jql),
		);
		$name and $update += compact('name');
		$response = jira_put('filter/' . $id, $update, $error, $info);
	}
	// Insert
	else {
		$response = jira_post('filter', array(
			'name' => trim($name),
			'jql' => trim($jql),
			'favourite' => 1,
		), $error, $info);
	}

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
<form autocomplete="off" action method="post">
	<p>Used if present, in order:</p>

	<p>1. Index filter: <select name="index_filter"><option>-- None<?= html_options($user->filter_options, $user->index_filter) ?></select></p>
	<p>2. Index query: <input name="index_query" value="<?= html($user->index_query) ?>" /></p>
	<p>3. Index project: <input name="index_project" value="<?= html($user->index_project) ?>" /></p>

	<p><input type="submit" /></p>
</form>

<h2>Add / edit filter</h2>

<form autocomplete="off" action method="post">
	<p>Filter: <select name="id"><option value="">-- NEW</option><?= html_options($user->filter_options) ?></select></p>
	<p>Name: <input name="name" /></p>
	<p>Query: <input name="jql" /></p>

	<p><input type="submit" /></p>
</form>

<script>
(function() {
	var filters = <?= json_encode($user->filter_options_jql) ?>,
		$select = $('select[name="id"]', 1),
		$textfield = $('input[name="jql"]', 1);
	$select.on('change', function(e) {
		$textfield.value = filters[this.value] || '';
	});
})();
</script>
<?php

echo '<pre>';
print_r($user->filters);
print_r($user->filter_options);
print_r($user->filter_options_jql);
print_r($user->filter_query_options);
echo '</pre>';

include 'tpl.footer.php';
