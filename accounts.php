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
	$db->delete('options', array('user_id' => $user->id));
	foreach ($_POST['config'] as $name => $value) {
		if ( isset(User::$_config[$name]) ) {
			$db->insert('options', array(
				'user_id' => $user->id,
				'name' => $name,
				'value' => implode(',', (array) $value),
			));
		}
	}

	return do_redirect('accounts');
}

// Unsync & re-sync
else if ( isset($_POST['unsync']) ) {
	$user->unsync();

	return do_redirect('accounts');
}

// Reset cookies
do_login('', '');

$_title = 'Accounts';
include 'tpl.header.php';

?>
<style>
label:after {
	content: '\A';
	white-space: pre;
}
:not(:checked) + .board-id {
	display: none;
}
</style>

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

<p><form method="post" action><button name="unsync" value="1">Reset cache</button></form></p>

<h2>Config for <span style="display: inline-block"><?= html($user->jira_id) ?></span></h2>

<form method="post">
	<table>
		<? foreach (array_filter(User::$_config) as $name => $info):
			$checkbox = $info['type'] == 'checkbox';
			?>
			<tr>
				<th align="right">
					<input
						id="config_<?= $name ?>"
						type="<?= $info['type'] ?>"
						name="config[<?= $name ?>]"
						<?if ($checkbox): ?>
							<?if ($user->config($name)):?>checked<?endif?>
						<?else:?>
							value="<?= html($user->config($name)) ?>"
						<?endif?>
						style="width: <?= $info['size'] ?>em"
						<?if ($info['required']):?>required<?endif?>
					/>
				</th>
				<td>
					<?if ($info['required']):?> <span class="required">*</span><?endif?>
					<label for="config_<?= $name ?>"><?= html($info['label']) ?></label>
				</td>
			</tr>
		<? endforeach ?>
		<tr>
			<th>Tempo</th>
			<td><a href="tempo.php">probably <?= $user->has_tempo ? '' : 'not' ?></a></td>
		</tr>
		<tr valign="top">
			<th align="right">Agile boards</th>
			<td style="white-space: normal">
				<? foreach ($user->agile_boards as $id => $name):
					$checked = in_array($id, $user->selected_agile_boards) ? 'checked' : '';
					?>
					<label>
						<input type="checkbox" name="config[agile_view_ids][]" value="<?= $id ?>" <?= $checked ?> />
						<span class="board-id">(<?= $id ?>)</span>
						<?= html($name) ?>
					</label>
				<? endforeach ?>
			</td>
		</tr>
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
