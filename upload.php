<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = @$_GET['key'];

$summary = @$_GET['summary'];

// Upload file
if ( isset($_FILES['file']) ) {
	$file = $_FILES['file'];

	if ( $file['error'] || !$file['size'] || !$file['tmp_name'] || !file_exists($file['tmp_name']) ) {
		exit('Upload error... Error # ' . $file['error']);
	}

	$filepath = realpath($file['tmp_name']);
	$filename = basename($file['name']);

	$insert = array(
		'file' => '@' . $filepath . ';filename=' . urlencode($filename),
	);
	$response = jira_upload('issue/' . $key . '/attachments', $insert, $error, $info);

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
<h2>Upload attachment</h2>

<form autocomplete="off" action method="post" enctype="multipart/form-data">
	<p>File: <input type="file" name="file" /></p>
	<p>Comment: <textarea name="comment" rows="4"></textarea></p>
	<p><input type="submit" /></p>
</form>

<?php

include 'tpl.footer.php';
