<!doctype html>
<html>

<head>
	<meta charset="utf-8" />
	<title><?= $_title ? "$_title | " : '' ?>Jira</title>
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="stylesheet" href="style.css" />
	<script src="rjs-custom.js"></script>
</head>

<body>

	<div class="ver-loader"></div>
	<div class="hor-loader"></div>

	<?if ($user): ?>
		<p class="top-menu">
			<a href="index.php">&lt; index</a> |
			<a href="new.php">+Issue</a> |
			<a href="auth.php">You (<?= $user->jira_user_short ?>)</a> |
			<a href="filters.php">Filters</a> |
			<a href="accounts.php">Accounts</a> |
			<a href="variables.php">Vars</a>
			<?if ($user->has_tempo):?>
				| <a href="tempo.php">Tempo</a>
			<?endif?>
			<?if ($user->config('agile_view_id')):?>
				| <a href="sprint.php">Sprint</a>
				| <a href="agile.php">Agile</a>
			<?endif?>
			| <a href="logout.php">Log out</a>
		</p>
	<? endif ?>
