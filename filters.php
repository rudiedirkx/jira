<?php

require 'inc.bootstrap.php';

do_logincheck();

if ( isset($_POST['index_filter'], $_POST['index_query'], $_POST['index_project']) ) {
	$user->save(array(
		'index_filter' => $_POST['index_filter'],
		'index_query' => $_POST['index_query'],
		'index_project' => $_POST['index_project'],
	));
	return do_redirect('index');
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
<?php

echo '<pre>';
print_r($user->filters);
print_r($user->filter_options);
print_r($user->filter_query_options);
echo '</pre>';

include 'tpl.footer.php';
