<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = @$_GET['key'];

$summary = @$_GET['summary'];

// Post worklog
if ( isset($_POST['spent'], $_POST['date'], $_POST['description']) ) {
	// $update = array(
		// 'worklog' => array(
			// array(
				// 'add' => array(
					// 'timeSpent' => $_POST['spent'],
					// 'started' => $_POST['date'],
					// 'comment' => $_POST['description'],
				// ),
			// ),
		// ),
	// );
	// $response = jira_put('issue/' . $key, compact('update'), $error, $info);

	$insert = array(
		'timeSpent' => $_POST['spent'],
		'started' => $_POST['date'],
		'comment' => $_POST['description'],
	);
	$response = jira_post('issue/' . $key . '/worklog', $insert, $error, $info);

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

echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . html($summary) . '</h1>';

?>
<h2>Log work</h2>

<form method="post">
	<p>Time spent: <input name="spent" placeholder="&quot;30m&quot; for 30 minutes, &quot;2h&quot; for 2 hours, etc" /></p>
	<p>Date started: <input type="datetime" name="date" value="<?= date('c') ?>" /></p>
	<p>Description: <input name="description" /></p>
	<p><input type="submit" /></p>
</form>

<?php

// echo '<pre>';
// print_r(jira_get('issue/' . $key . '/worklog', null, $error, $info));

include 'tpl.footer.php';
