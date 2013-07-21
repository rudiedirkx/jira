<?php

require 'inc.bootstrap.php';

do_logincheck();

$accounts = get_accounts();

// Add another account
if ( isset($_POST['url'], $_POST['user'], $_POST['pass']) ) {
	$url = trim($_POST['url']);
	$auth = trim($_POST['user']) . ':' . $_POST['pass'];
	do_login($url, $auth);

	// Save user to local db for preferences
	try {
		$db->insert('users', array(
			'jira_url' => $url,
			'jira_user' => trim($_POST['user']),
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

// Reset cookies
do_login('', '');

include 'tpl.header.php';

?>

<h1>Accounts</h1>

<ul>
	<?foreach ($accounts as $i => $account):?>
		<li class="<?if ($account->active):?>active-account<?endif?>">
			<?= $account->user ?> @ <?= $account->url ?>
			<?if (!$account->active):?>
				(<a href="?switch=<?= $i ?>">switch</a>)
				(<a href="?unlink=<?= $i ?>">x</a>)
			<?endif?>
		</li>
	<?endforeach?>
</ul>

<h2>Add account</h2>

<?php
$_COOKIE['JIRA_URL'] = '';
include 'tpl.login.php';
?>

<?php

include 'tpl.footer.php';
