<?php

require __DIR__ . '/env.php';
require __DIR__ . '/autoload.php';
require __DIR__ . '/classes.php';

header('Content-type: text/plain; charset=utf-8');

if ( !empty($_SERVER['REMOTE_ADDR']) && !in_array($_SERVER['REMOTE_ADDR'], explode(',', RDX_JIRA_LIB_TEST_ALLOWED_IPS)) ) {
	exit('Access denied for ' . $_SERVER['REMOTE_ADDR']);
}
