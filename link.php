<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = @$_GET['key'];

$summary = @$_GET['summary'];

// Create link
if ( isset($_POST['type'], $_POST['issue']) ) {
	$type = explode(':', $_POST['type']);
	$otherIssueXward = $type[1];
	$thisIssueXward = $otherIssueXward == 'inward' ? 'outward' : 'inward';

	$data = array(
		'type' => array('name' => $type[0]),
		$thisIssueXward . 'Issue' => array('key' => $key),
		$otherIssueXward . 'Issue' => array('key' => $_POST['issue']),
	);

	$response = jira_post('issueLink', $data, $error, $info);

	if ( !$error ) {
		return do_redirect('issue', array('key' => $key));
	}

	echo '<pre>';
	print_r($data);
	var_dump($error);
	print_r($response);
	print_r($info);

	exit;
}

$linkTypes = jira_get('issueLinkType');
$linkTypeOptions = array_reduce($linkTypes->issueLinkTypes, function($options, $type) {
	$options[$type->name . ':inward'] = $type->inward;
	if ($type->outward != $type->inward) {
		$options[$type->name . ':outward'] = $type->outward;
	}
	return $options;
});

include 'tpl.header.php';

echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . html($summary) . '</h1>';

?>
<h2>Create link</h2>

<form autocomplete="off" method="post">
	<p>This issue <select name="type"><?= html_options($linkTypeOptions, 'Relates:inward') ?></select></p>
	<p>That issue: <input name="issue" placeholder="ABC-123" /></p>
	<p><input type="submit" /></p>
</form>

<?php

include 'tpl.footer.php';
