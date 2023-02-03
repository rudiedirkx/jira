<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = &$_GET['key'];
$id = @$_GET['id'];

// Fetch labels autocomplete
if ( isset($_GET['label']) ) {
	$searched = strtolower(trim($_GET['label']));
	$rsp = jira_get('label', array(), $error, $info);
	$labels = array_values(array_filter($rsp->values, function(string $found) use ($searched) {
		return strpos($found, $searched) === 0;
	}));
	natcasesort($labels);

	header('Content-type: text/json; charset=utf-8');
	echo json_encode(compact('labels'));
	exit;
}

// Update labels
else if ( isset($_POST['old_labels'], $_POST['new_labels']) ) {
	$old_labels = array_filter(explode(' ', $_POST['old_labels']));
	$new_labels = array_filter(explode(' ', $_POST['new_labels']));

	$all_labels = array_unique(array_merge($old_labels, $new_labels));

	$update = array();
	foreach ( $all_labels AS $label ) {
		$in_old = in_array($label, $old_labels);
		$in_new = in_array($label, $new_labels);
		if ( $in_old && !$in_new ) {
			$update['labels'][] = array('remove' => $label);
		}
		else if ( !$in_old && $in_new ) {
			$update['labels'][] = array('add' => $label);
		}
	}

	if ( $update ) {
		$response = jira_put('issue/' . $key, compact('update'), $error, $info);
	}

	if ( !$update || !$error ) {
		return do_redirect('issue', array('key' => $key));
	}

	echo '<pre>';
	print_r($update);
	var_dump($error);
	print_r($response);
	print_r($info);

	exit;
}

$issue = jira_get('issue/' . $key);
$id = $issue->id;

$_title = "Labels $key";
include 'tpl.header.php';

echo '<h1><a href="issue.php?key=' . $key . '">' . $key . '</a> ' . html($issue->fields->summary) . '</h1>';

?>
<h2>Labels</h2>

<form autocomplete="off" method="post">
	<input type="hidden" name="old_labels" value="<?= html(implode(' ', $issue->fields->labels)) ?>" />
	<p><input id="ls" name="new_labels" value="<?= html(implode(' ', $issue->fields->labels)) ?> " size="60" autocapitalize="off" /></p>
	<p>(<a id="fl" href="#">fetch</a>) <button>Save</button></p>
</form>

<p>Suggestions: <span id="ss"></span></p>

<script>
$('ss').on('click', 'a', function(e) {
	e.preventDefault();
	var label = this.data('label');
	var ls = $('ls');
	var curLabels = ls.value;

	// Append
	if ( curLabels.match(/ $/) ) {
		curLabels += label + ' ';
	}
	// Replace
	else {
		curLabels = (' ' + curLabels).replace(/ \w+$/, ' ' + label).trim() + ' ';
	}

	ls.value = curLabels;
});

$('fl').on('click', function(e) {
	e.preventDefault();
	var labels = $('ls').value.trim().split(/ /g);
	var label = labels[labels.length-1];

	document.body.classList.add('loading');
	$.get('?id=<?= $id ?>&label=' + label).on('done', function(e, rsp) {
		var html = '';
		r.each(rsp.labels, function(label) {
			html += ' [<a data-label="' + label + '" href="#">' + label + '</a>] ';
		});
		$('ss').setHTML(html || 'no results');
		document.body.classList.remove('loading');
	});
});
</script>
<?php

// echo '<p>[' . implode('] [', $labels) . ']</p>';

// echo '<pre>';
// print_r($labels);
// var_dump($error);
// print_r($info);

include 'tpl.footer.php';
