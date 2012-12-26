<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = $_GET['key'];
$id = $_GET['id'];

if ( isset($_POST['labels']) ) {
	$old_labels = (array)@$_GET['labels'];
	$new_labels = array_filter(explode(' ', $_POST['labels']));

	$all_labels = array_unique(array_merge($old_labels, $new_labels));

	$update = array();
	foreach ( $all_labels AS $label ) {
		$in_old = in_array($label, $old_labels);
		$in_new = in_array($label, $new_labels);
		if ( $in_old && !$in_new ) {
			$update['labels'][] = array('remove' => $label);
		}
		else if ( !$in_old && $in_new ) {
			$update['labels'][] = array('add' => $label);
		}
	}

	$response = jira_put('issue/' . $key, compact('update'), $error, $info);

	if ( !$error ) {
		return do_redirect('issue', array('key' => $key));
	}

	echo '<pre>';
	print_r($update);
	var_dump($error);
	print_r($response);
	print_r($info);

	exit;
}

$suggestions = jira_get(JIRA_API_1_PATH . 'labels/' . $id . '/suggest.json', array('query' => ''), $error, $info);
$labels = (array)@$_GET['labels'];
foreach ( $suggestions->suggestions AS $label ) {
	$labels[] = (string)$label->label;
}
natcasesort($labels);

echo '<p class="menu"><a href="index.php">&lt; index</a></p>';
echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> Labels</h1>';

?>
<form method="post">
	<p><input name="labels" value="<?= implode(' ', (array)@$_GET['labels']) ?>" size="60" /></p>
	<p><input type="submit" /></p>
</form>
<?php

echo '<p>[' . implode('] [', $labels) . ']</p>';

// echo '<pre>';
// print_r($labels);
// var_dump($error);
// print_r($info);
