<?php

require 'inc.bootstrap.php';

do_logincheck();

echo '<pre>';

$user->unsync();

print_r($user->filters);
print_r($user->filter_query_options);

echo implode("\n", $jira_requests) . "\n";
