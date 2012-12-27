<?php

require 'inc.bootstrap.php';

do_logincheck();

$filters = jira_get('filter/favourite', null, $error, $info);

echo '<pre>';
print_r($filters);
var_dump($error);
print_r($info);
