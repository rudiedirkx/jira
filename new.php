<?php

require 'inc.bootstrap.php';

do_logincheck();

if ( isset($_POST['preview']) ) {
	$rsp = jira_post(JIRA_API_1_PATH . 'render', array(
		'issueKey' => '',
		'rendererType' => 'atlassian-wiki-renderer',
		'unrenderedMarkup' => trim($_POST['preview']),
	), $error, $info);
	if ( $error ) {
		echo '<pre>';
		var_dump($error);
		print_r($rsp);
		print_r($info);
	}
	else {
		echo $rsp;
	}
	exit;
}

if ( isset($_GET['project'], $_GET['issuetype'], $_POST['summary'], $_POST['description'], $_POST['priority']) ) {
	$fields = array(
		'project' => array('id' => $_GET['project']),
		'issuetype' => array('id' => $_GET['issuetype']),
		'summary' => trim($_POST['summary']),
		'description' => trim($_POST['description']),
		'priority' => array('id' => $_POST['priority']),
	);
	if ( !empty($_POST['parent']) ) {
		$fields['parent'] = array('key' => $_POST['parent']);
	}

	$response = jira_post('issue', compact('fields'), $error, $info);

	if ( !$error ) {
		$key = $response->key;
		return do_redirect('issue', compact('key'));
	}

	echo '<pre>';
	print_r($fields);
	var_dump($error);
	print_r($response);
	print_r($info);

	exit;
}

$project = @$_GET['project'];
$issuetype = @$_GET['issuetype'];

$parent = (string)@$_GET['parent'];
$parentsummary = (string)@$_GET['parentsummary'];

$_title = 'New issue';
include 'tpl.header.php';

echo '<h1>Create new issue</h1>';



// Show parent issue
if ( $parent && $parentsummary ) {
	echo '<h2>Parent issue</h2>';

	echo '<ul>';
	echo '<li>';
	echo '<a href="issue.php?key=' . urlencode($parent) . '">' . html($parent) . ' ' . html($parentsummary) . '</a>';
	echo '</li>';
	echo '</ul>';
}



// Choose project
echo '<h2>Project</h2>';

if ( !$project ) {
	$projects = jira_get('project', array(), $error, $info);
	echo '<ul>';
	foreach ( $projects AS $project ) {
		echo '<li>';
		echo '<a href="new.php?project=' . $project->id . '">' . html($project->name) . '</a>';
		echo '</li>';
	}
	echo '</ul>';
	exit;
}

// Get project meta data
$meta = jira_get('issue/createmeta', array(
	'expand' => 'projects.issuetypes.fields',
	'projectIds' => $project,
	'issuetypeIds' => $issuetype ?: null,
), $error, $info);
$project = $meta->projects[0];

// Show selected project
echo '<ul>';
echo '<li>';
echo '<a href="new.php?project=' . $project->id . '">' . html($project->name) . '</a>';
echo ' (<a href="new.php?parent=' . urlencode($parent) . '">change</a>)';
echo '</li>';
echo '</ul>';



// Choose issue type
echo '<h2>Issue type</h2>';

if ( !$issuetype ) {
	echo '<ul>';
	$subtask = !empty($parent);
	foreach ( $project->issuetypes AS $issuetype ) {
		if ( $issuetype->subtask == $subtask ) {
			echo '<li>';
			echo '<a href="new.php?project=' . $project->id . '&issuetype=' . $issuetype->id . '&parent=' . urlencode($parent) . '&parentsummary=' . urlencode($parentsummary) . '">' . $issuetype->name . '</a>';
			echo '</li>';
		}
	}
	echo '</ul>';
	exit;
}

// Get issue type meta data
$issuetype = get_issuetype($project, $issuetype);

// Show selected issue type
echo '<ul>';
echo '<li>';
echo '<a href="new.php?project=' . $project->id . '&issuetype=' . $issuetype->id . '">' . $issuetype->name . '</a>';
echo ' (<a href="new.php?project=' . $project->id . '&parent=' . urlencode($parent) . '&parentsummary=' . urlencode($parentsummary) . '">change</a>)';
echo '</li>';
echo '</ul>';



// Get priority meta data
$priorities = array_reduce($issuetype->fields->priority->allowedValues, function($list, $priority) {
	$list[ $priority->id ] = $priority->name;
	return $list;
});
$priorityKeys = array_keys($priorities);
$defaultPriority = $priorityKeys[ floor((count($priorities) - 1) / 2) ];

?>
<form autocomplete="off" action method="post">
	<? if ($parent && $parentsummary): ?>
		<input type="hidden" name="parent" value="<?= html($parent) ?>" />
		<p>Parent issue: <input readonly disabled value="<?= html($parent . ' ' . $parentsummary) ?>" /></p>
	<? endif ?>
	<p>Summary: <input name="summary" /></p>
	<p>Description: <textarea name="description" rows="8"></textarea><br><button type="button" data-preview="textarea[name=description]">Preview</button></p>
	<p>Priority: <select name="priority"><?= html_options($priorities, $defaultPriority) ?></select></p>

	<p><input type="submit" /></p>
</form>
<?php

if ( isset($_GET['debug']) ) {
	echo '<pre>' . print_r($issuetype, 1) . '</pre>';
}

include 'tpl.footer.php';



function get_issuetype( $project, $is_issuetype ) {
	foreach ( $project->issuetypes AS $issuetype ) {
		if ( $issuetype->id == $is_issuetype ) {
			return $issuetype;
		}
	}
}
