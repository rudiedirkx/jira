<?php

namespace foo;

require __DIR__ . '/env.php';
require __DIR__ . '/autoload.php';
require __DIR__ . '/classes.php';

header('Content-type: text/plain; charset=utf-8');

if ( !empty($_SERVER['REMOTE_ADDR']) && !in_array($_SERVER['REMOTE_ADDR'], explode(',', RDX_JIRA_LIB_TEST_ALLOWED_IPS)) ) {
	exit('Access denied for ' . $_SERVER['REMOTE_ADDR']);
}

use rdx\jira\Config;
use rdx\jira\BasicAuth;
use rdx\jira\Client;

$config = new Config('https://jira.ezcompany.nl/jira', array(
	'Transport' => 'rdx\jira\CurlTransport',
	'Response' => 'MySuperResponse', // @see classes.php
	'User' => 'MySuperUser', // @see classes.php
));
print_r($config);
$auth = new BasicAuth(RDX_JIRA_LIB_TEST_USER, RDX_JIRA_LIB_TEST_PASS);
$client = new Client($config, $auth);

// Custom request
// $request = $client->get('user');
// $request->build();
// $response = $request->send();
// print_r($client);
// print_r($request);
// print_r($response);

// Fetch user
$user = $client->user(RDX_JIRA_LIB_TEST_USER);
print_r($user);

// Fetch issue
// $issue = $client->open(RDX_JIRA_LIB_TEST_ISSUE);
// print_r($issue);
