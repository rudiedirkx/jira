<!doctype html>
<html>

<head>
	<meta charset="utf-8" />
	<title>Mobile Jira</title>
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="stylesheet" href="style.css" />
	<script src="rjs-custom.js"></script>
</head>

<body>

	<div class="ver-loader"></div>
	<div class="hor-loader"></div>

	<p class="top-menu">
		<?if (!$index):?><a href="index.php">&lt; index</a> | <?endif?>
		<a href="auth.php">You (<?= $user->jira_user ?>)</a> |
		<a href="filters.php">Filters</a> |
		<a href="accounts.php">Accounts</a> |
		<a href="variables.php">Vars</a> |
		<a href="tempo.php">Tempo</a>
		<?if ($user->config('agile_view_id')):?> | <a href="agile.php">Agile</a><?endif?>
		<?if ($index):?> | <a href="logout.php">Log out</a><?endif?>
	</p>
