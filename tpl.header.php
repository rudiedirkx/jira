<!doctype html>
<html>

<head>
	<meta charset="utf-8" />
	<title>Mobile Jira</title>
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="stylesheet" href="style.css" />
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
</head>

<body>

<p class="top-menu">
	<a href="index.php">&lt; index</a> |
	<a href="auth.php">You (<?= $user->jira_user ?>)</a> |
	<a href="filters.php">Filters</a> |
	<a href="accounts.php">Accounts</a> |
	<a href="logout.php">Log out</a>
</p>
