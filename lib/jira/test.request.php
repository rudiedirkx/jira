<?php

require __DIR__ . '/bootstrap.php';

use rdx\jira\Config;
use rdx\jira\BasicAuth;
use rdx\jira\Client;
use rdx\jira\Auth1Request;

$config = new Config('https://jira.ezcompany.nl/jira', array(
	'Response' => 'MySuperResponse', // @see classes.php
));
$auth = new BasicAuth(RDX_JIRA_LIB_TEST_USER, RDX_JIRA_LIB_TEST_PASS);
$client = new Client($config, $auth);

print_r($client);

// Custom request
$request = $client->get('user');
$request->build();
$response = $request->send();

print_r($request);
print_r($response);
