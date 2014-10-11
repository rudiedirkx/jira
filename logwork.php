<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = @$_GET['key'];
$id = @$_GET['id'];

$summary = @$_GET['summary'];

// Post worklog
if ( isset($_POST['spent'], $_POST['date'], $_POST['description']) ) {
	$data = array(
		'timeSpent' => $_POST['spent'],
		'started' => $_POST['date'],
		'comment' => $_POST['description'],
	);

	if ( $id ) {
		$response = jira_put('issue/' . $key . '/worklog/' . $id, $data, $error, $info);
	}
	else {
		$response = jira_post('issue/' . $key . '/worklog', $data, $error, $info);
	}

	if ( !$error ) {
		return do_redirect('issue', array('key' => $key));
	}

	echo '<pre>';
	print_r($insert);
	var_dump($error);
	print_r($response);
	print_r($info);

	exit;
}

include 'tpl.header.php';

$worklog = $id ? jira_get('issue/' . $key . '/worklog/' . $id) : false;

echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . html($summary) . '</h1>';

?>
<h2><?= $worklog ? 'Edit worklog # ' . $worklog->id : 'Log work' ?></h2>

<form autocomplete="off" method="post">
	<p>Time spent: <input name="spent" placeholder="&quot;30m&quot; for 30 minutes, &quot;2h&quot; for 2 hours, etc" value="<?= html(@$worklog->timeSpent) ?>" /></p>
	<p>Date started: <input type="datetime" name="date" value="<?= $worklog ? $worklog->started : date('Y-m-d\TH:i:s.000O') ?>" /></p>
	<p>Description: <input name="description" value="<?= html(@$worklog->comment) ?>" /></p>
	<p><input type="submit" /></p>
</form>

<?php

// echo '<pre>';
// print_r(jira_get('issue/' . $key . '/worklog', null, $error, $info));

include 'tpl.footer.php';
