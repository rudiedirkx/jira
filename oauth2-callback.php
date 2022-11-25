<?php

require 'inc.bootstrap.php';

$info = [
	'JIRA_AUTH' => false,
];
$rsp = jira_post('https://auth.atlassian.com/oauth/token', [
	'grant_type' => 'authorization_code',
	'client_id' => OAUTH_CLENT_ID,
	'client_secret' => OAUTH_CLENT_SECRET,
	'code' => $_GET['code'],
	'redirect_uri' => OAUTH_REDIRECT_URL,
], $error, $info);
if ($error) {
	echo '<pre>';
	var_dump($error);
	echo $info['response'] . "\n";
	echo '<p><a href="auth.php">Retry</a></p>';
	exit;
}

$auth = 'Bearer ' . $rsp->access_token;
$info = [
	'JIRA_AUTH' => $auth,
	'unauth_ok' => true,
];
$rsp = jira_get('https://api.atlassian.com/oauth/token/accessible-resources', [], $error, $info);
if ($error) {
	echo '<pre>';
	var_dump($error);
	print_r($info);
	echo '<p><a href="auth.php">Retry</a></p>';
	exit;
}

$server = $rsp[0]->url;
$url = sprintf('https://api.atlassian.com/ex/jira/%s', $rsp[0]->id);

$info = [
	'JIRA_AUTH' => $auth,
	'unauth_ok' => true,
];
$rsp = jira_get("$url/rest/api/3/myself", [], $error, $info);
// $rsp = jira_get("$url/rest/auth/1/session", [], $error, $info);
if ($error) {
	echo '<pre>';
	var_dump($error);
	print_r($info);
	echo '<p><a href="auth.php">Retry</a></p>';
	exit;
}

$username = $rsp->emailAddress;

do_login($url, $info['JIRA_AUTH'], $username, $server);

return do_redirect('index');
