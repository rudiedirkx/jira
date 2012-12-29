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

else if ( isset($_GET['delete'], $_POST['confirm']) ) {
	$response = jira_delete('issue/' . $key . '/comment/' . $id, null, $error, $info);

	if ( !$error ) {
		return do_redirect('issue', array('key' => $key));
	}

	echo '<pre>';
	var_dump($error);
	print_r($response);
	print_r($info);

	exit;
}

include 'tpl.header.php';

echo '<p class="menu"><a href="index.php">&lt; index</a></p>';
echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . html($summary) . '</h1>';

echo '<h2>Comment # ' . $id . '</h2>';

if ( isset($_GET['delete']) ) {
	echo '<p>You SURE you wanna DELETE this comment?</p>';
	echo '<form method="post"><p>';
	echo '<input type="hidden" name="confirm" value="1" />';
	echo '<input type="submit" value="YES, delete it" /> ';
	echo '<a href="javascript:history.go(-1);void(0);">Nooooo</a>';
	echo '</p></form>';
	exit;
}

$comment = jira_get('issue/' . $key . '/comment/' . $id, null, $error, $info);

?>

<form method="post">
	<p><textarea name="comment" rows="8"><?= html($comment->body) ?></textarea></p>
	<p>
		<input type="submit" />
		<a href="comment.php?key=<?= $key ?>&id=<?= $id ?>&delete=1">delete</a>
	</p>
</form>
<?php

// echo '<pre>';
// print_r($comment);
// var_dump($error);
// print_r($info);

include 'tpl.footer.php';
