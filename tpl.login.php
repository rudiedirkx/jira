
<form method="post">
	<p>Server URL: <input type="url" name="url" size="60" value="<?= @reset(explode(',', @$_COOKIE['JIRA_URL'])) ?>" placeholder="https://YOUR.jira.com WITHOUT /rest at the end" /></p>
	<p>Username: <input name="user" /></p>
	<p>Password: <input name="pass" type="password" /></p>
	<p><input type="submit" /></p>
</form>
