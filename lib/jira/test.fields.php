<?php

require __DIR__ . '/bootstrap.php';

use rdx\jira\Config;
use rdx\jira\BasicAuth;
use rdx\jira\Client;
use rdx\jira\FileCache;

$client = new Client(
	new Config(RDX_JIRA_LIB_TEST_URL, array(
		'FileCache' => array('dir' => __DIR__ . '/cache'),
	)),
	new BasicAuth(RDX_JIRA_LIB_TEST_USER, RDX_JIRA_LIB_TEST_PASS),
	new FileCache
);
print_r($client);

// Fetch custom field mapping, from cache, or not
$mapping = $client->cache->custom_field_mapping;
print_r($mapping);

// Fetch custom fields, from cache, or not
$custom = $client->cache->custom_fields;
print_r($custom);
