<?php

require 'inc.bootstrap.php';

do_logincheck();

$id = $_GET['id'];
$thumbnail = isset($_GET['thumbnail']);

$attachment = jira_get('attachment/' . $id);

$url = $attachment->content;
if ( $thumbnail ) {
	if ( empty($attachment->thumbnail) ) {
		header('HTTP/1.1 404 Not Found');
		exit("This attachment doesn't have a thumbnail.");
	}
	$url = $attachment->thumbnail;
}

$context = stream_context_create(array('http' => array(
	'header' => 'Authorization: Basic ' . base64_encode(JIRA_AUTH),
)));
$data = file_get_contents($url, FALSE, $context);

header('Content-type: ' . $attachment->mimeType);
header('Content-disposition: inline; filename=' . basename($attachment->filename));
echo $data;
