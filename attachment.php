<?php

require 'inc.bootstrap.php';

do_logincheck();

$id = $_GET['id'];
$thumbnail = isset($_GET['thumbnail']);

$attachment = jira_get('attachment/' . $id);

$context = stream_context_create(array('http' => array(
	'header' => 'Authorization: Basic ' . base64_encode(JIRA_AUTH),
)));
$url = $thumbnail ? $attachment->thumbnail : $attachment->content;
$data = file_get_contents($url, FALSE, $context);

header('Content-type: ' . $attachment->mimeType);
header('Content-disposition: inline; filename=' . basename($attachment->filename));
echo $data;
