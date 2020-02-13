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
		'file' => curl_file_create($filepath, null, $filename),
	);
	$response = jira_upload('issue/' . $key . '/attachments', $insert, $error, $info);

	if ( !$error ) {
		$comment = trim($_POST['comment'] ?? '');
		if ( strlen($comment) ) {
			$response2 = jira_post('issue/' . $key . '/comment', array('body' => $comment), $error2, $info2);
		}

		return do_redirect('issue', array('key' => $key));
	}

	echo '<pre>';
	print_r($insert);
	var_dump($error);
	print_r($response);
	print_r($info);

	exit;
}

$_title = "Upload $key";
include 'tpl.header.php';

echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . html($summary) . '</h1>';

?>
<h2>Upload attachment</h2>

<form autocomplete="off" action method="post" enctype="multipart/form-data">
	<p>File: <input type="file" name="file" /></p>
	<p>Comment: <textarea name="comment" rows="4"></textarea><br><button type="button" data-preview="textarea[name=comment]">Preview</button></p>
	<p><input type="submit" /></p>
</form>

<?php

include 'tpl.footer.php';
