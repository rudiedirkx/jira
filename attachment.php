<?php

require 'inc.bootstrap.php';

do_logincheck();

$id = $_GET['id'];

$attachment = jira_get('attachment/' . $id);

$context = stream_context_create(array('http' => array(
	'header' => 'Authorization: Basic ' . base64_encode(JIRA_AUTH),
)));
$data = file_get_contents($attachment->content, FALSE, $context);

header('Content-type: ' . $attachment->mimeType);
header('Content-disposition: inline; filename=' . basename($attachment->filename));
echo $data;
