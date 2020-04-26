<?php

require 'inc.bootstrap.php';

do_logincheck();

$id = $_GET['id'];
$thumbnail = (bool) ($_GET['thumbnail'] ?? 0);

$attachment = jira_get('attachment/' . $id);

$url = $attachment->content;
if ( $thumbnail ) {
	if ( empty($attachment->thumbnail) ) {
		header('HTTP/1.1 404 Not Found');
		exit("This attachment doesn't have a thumbnail.");
	}
	$url = $attachment->thumbnail;
}

$data = jira_download($url);

header('Content-type: ' . $attachment->mimeType);
header('Content-disposition: inline; filename=' . basename($attachment->filename));
echo $data;
