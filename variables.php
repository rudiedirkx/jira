<?php

require 'inc.bootstrap.php';

do_logincheck();

if ( isset($_POST['manual_update']) ) {
	$user->syncAnyAutoVar($_POST['manual_update']);
	return do_redirect('variables');
}

else if ( isset($_POST['name'], $_POST['regex'], $_POST['replacement'], $_POST['value']) ) {
	$invalid = count(array_filter($_POST)) != count($_POST);
	if ( !$invalid ) {
		$db->insert('variables', array(
			'user_id' => $user->id,
			'name' => trim($_POST['name']),
			'regex' => trim($_POST['regex']),
			'replacement' => trim($_POST['replacement']),
			'value' => trim($_POST['value']),
		));
	}
	return do_redirect('variables');
}

else if ( isset($_POST['v']) ) {
	print_r($_POST['v']);
	foreach ( $_POST['v'] AS $id => $var ) {
		$db->update('variables', $var, array('id' => $id, 'user_id' => $user->id));
	}

	return do_redirect('variables');
}

include 'tpl.header.php';

?>
<style>
body:not(.show-editables) .editable { display: none; }
</style>

<h1>Your variables</h1>

<form autocomplete="off" action method="post">
	<table border=1>
		<thead>
			<tr>
				<th>Name</th>
				<th class="editable">Regex</th>
				<th class="editable">Replacement (XXX)</th>
				<th>Value</th>
				<th class="editable">Auto update type</th>
			</tr>
		</thead>
		<tbody>
			<? foreach ($user->variables as $var): ?>
				<tr>
					<td>
						<input name="v[<?= $var->id ?>][name]" value="<?= html($var->name) ?>" size="10" class="manual-width" />
					</td>
					<td class="editable">
						<input name="v[<?= $var->id ?>][regex]" value="<?= html($var->regex) ?>" />
					</td>
					<td class="editable">
						<input name="v[<?= $var->id ?>][replacement]" value="<?= html($var->replacement) ?>" />
					</td>
					<td>
						<input name="v[<?= $var->id ?>][value]" value="<?= html($var->value) ?>" size="6" class="manual-width" />
						<?if ($var->auto_update_type): ?>
							<button name="manual_update" value="<?= $var->auto_update_type ?>">&lt;</button>
						<?endif?>
					</td>
					<td class="editable">
						<select name="v[<?= $var->id ?>][auto_update_type]">
							<?= html_options(array('sprint' => 'Current sprint'), $var->auto_update_type, 'Custom') ?>
						</select>
					</td>
				</tr>
			<? endforeach ?>
		</tbody>
	</table>
	<p>
		(<a class="toggle-editables" href>edit</a>)
		<input type="submit" />
	</p>
</form>

<h2>Add variable</h2>

<form autocomplete="off" action method="post">
	<p>Name: <input name="name" placeholder="Sprint #" /></p>
	<p>Regex: <input name="regex" placeholder="sprint = \d+" /></p>
	<p>Replacement: <input name="replacement" placeholder="sprint = XXX" /></p>

	<p><input type="submit" /></p>
</form>

<script>
$('.toggle-editables', true).on('click', function(e) {
	e.preventDefault();

	document.body.toggleClass('show-editables');
});
</script>
<?php

echo '<pre>';
print_r($user->filter_query_options);
echo '</pre>';

include 'tpl.footer.php';
