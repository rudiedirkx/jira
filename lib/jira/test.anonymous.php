<?php

require __DIR__ . '/bootstrap.php';

use rdx\jira\Config;
use rdx\jira\NoAuth;
use rdx\jira\Client;
use rdx\jira\Api2Request;
use rdx\jira\Auth1Request;

$config = new Config(RDX_JIRA_LIB_TEST_URL);
$auth = new NoAuth;
$client = new Client($config, $auth);

// Auth request (non-std api path)
$request = new Auth1Request($client, 'GET', 'session');
$response = $request->send();
// print_r($request);
print_r($response);

// Server info should be accessible freely
$request = new Api2Request($client, 'GET', 'serverInfo');
$response = $request->send();
// print_r($request);
print_r($response);
