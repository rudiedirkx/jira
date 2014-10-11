<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = $_GET['key'];
$id = $_GET['id'];

$summary = $_GET['summary'];

if ( isset($_POST['comment']) ) {
	$update = array('body' => $_POST['comment']);
	$response = jira_put('issue/' . $key . '/comment/' . $id, $update, $error, $info);

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

include 'tpl.header.php';

$comment = jira_get('issue/' . $key . '/comment/' . $id, null, $error, $info);

echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . html($summary) . '</h1>';

echo '<h2>Comment # ' . $id . '</h2>';

?>

<form autocomplete="off" method="post">
	<p><textarea name="comment" rows="8"><?= html($comment->body) ?></textarea></p>
	<p>
		<input type="submit" />
		or
		<a data-confirm="DELETE this COMMENT for ever and ever?" href="issue.php?key=<?= $key ?>&delete_comment=<?= $id ?>">delete</a>
	</p>
</form>
<?php

// echo '<pre>';
// print_r($comment);
// var_dump($error);
// print_r($info);

include 'tpl.footer.php';
