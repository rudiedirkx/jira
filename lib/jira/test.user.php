<?php

require __DIR__ . '/bootstrap.php';

use rdx\jira\Config;
use rdx\jira\BasicAuth;
use rdx\jira\Client;
use rdx\jira\Auth1Request;

$config = new Config('https://jira.ezcompany.nl/jira', array(
	'User' => 'MySuperUser', // @see classes.php
));
$auth = new BasicAuth(RDX_JIRA_LIB_TEST_USER, RDX_JIRA_LIB_TEST_PASS);
$client = new Client($config, $auth);

// Fetch user
$user = $client->user(RDX_JIRA_LIB_TEST_USER);
print_r($user);
