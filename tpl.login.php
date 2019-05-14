
<form autocomplete="off" method="post">
	<p>Server URL: <input type="url" name="url" size="60" value="<?= @reset(explode(',', @$_COOKIE['JIRA_URL'])) ?>" placeholder="https://YOUR.jira.com WITHOUT /rest at the end" /></p>
	<p>Jira username / Atlassian e-mail: <input name="user" /></p>
	<p>Jira password / Personal API token: <input name="pass" type="password" /></p>
	<p><input type="submit" /></p>
</form>
