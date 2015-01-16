<?php

require 'inc.bootstrap.php';

do_logincheck();

$accounts = get_accounts();

// Add another account
if ( isset($_POST['url'], $_POST['user'], $_POST['pass']) ) {
	$url = trim($_POST['url'], ' /');
	$username = trim($_POST['user']);
	if ( !jira_test($url, $username, $_POST['pass'], $info) ) {
		exit($info['error2']);
	}

	// Save credentials to cookie
	$auth = $username . ':' . $_POST['pass'];
	do_login($url, $auth);

	// Save user to local db for preferences
	try {
		$db->insert('users', array(
			'jira_url' => $url,
			'jira_user' => $username,
		));
	}
	catch ( db_exception $ex ) {
		// Let's assume it failed because the user already exists.
	}

	return do_redirect('accounts');
}

// Switch to other account
else if ( isset($_GET['switch']) ) {
	$index = $_GET['switch'];

	if ( !isset($accounts[$index]) ) {
		exit('Invalid index');
	}

	// Move account to # 0
	$account = $accounts[$index];
	unset($accounts[$index]);
	array_unshift($accounts, $account);

	do_login('', '', $accounts);

	return do_redirect('accounts');
}

// Unlink account
else if ( isset($_GET['unlink']) ) {
	$index = $_GET['unlink'];

	if ( !isset($accounts[$index]) || $accounts[$index]->active ) {
		exit('Invalid index');
	}

	unset($accounts[$index]);
	do_login('', '', $accounts);

	return do_redirect('accounts');
}

// Save user config/options
else if ( isset($_POST['config']) ) {
	foreach ($_POST['config'] as $name => $value) {
		if ( isset(User::$_config[$name]) ) {
			$db->replace('options', array(
				'user_id' => $user->id,
				'name' => $name,
				'value' => $value,
			));
		}
	}

	return do_redirect('accounts');
}

// Reset cookies
do_login('', '');

include 'tpl.header.php';

?>

<h1>Accounts</h1>

<ul>
	<?foreach ($accounts as $i => $account):
		$_url = parse_url($account->url);
		?>
		<li class="<?if ($account->active):?>active-account<?endif?>">
			<?= $account->user ?> @ <?= $_url['host'] ?>
			<?if (!$account->active):?>
				(<a href="?switch=<?= $i ?>">switch</a>)
				(<a href="?unlink=<?= $i ?>">x</a>)
			<?endif?>
		</li>
	<?endforeach?>
</ul>

<p><a href="logout.php">Log out all accounts</a></p>

<h2>Config for <span style="display: inline-block"><?= html($user->jira_id) ?></span></h2>

<form method="post">
	<table>
		<? foreach (User::$_config as $name => $info): ?>
			<tr>
				<th align="right">
					<?= html($info['label']) ?>
					<?if ($info['required']):?> <span class="required">*</span><?endif?>
				</th>
				<td>
					<input
						type="<?= $info['type'] ?>"
						name="config[<?= $name ?>]"
						value="<?= html($user->config($name)) ?>"
						style="width: <?= $info['size'] ?>em"
						<?if ($info['required']):?>required<?endif?>
					/>
				</td>
			</tr>
		<? endforeach ?>
	</table>
	<p><button>Save</button></p>
</form>

<h2>Add account</h2>

<?php
$_COOKIE['JIRA_URL'] = '';
include 'tpl.login.php';
?>

<?php

include 'tpl.footer.php';
