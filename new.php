<?php

require 'inc.bootstrap.php';

do_logincheck();

if ( isset($_GET['project'], $_GET['issuetype'], $_POST['summary'], $_POST['description'], $_POST['priority'], $_POST['assignee']) ) {
	$fields = array(
		'project' => array('id' => $_GET['project']),
		'issuetype' => array('id' => $_GET['issuetype']),
		'summary' => $_POST['summary'],
		'description' => $_POST['description'],
		'priority' => array('id' => $_POST['priority']),
	);
	if ( !empty($_POST['assignee']) ) {
		$fields['assignee'] = array('name' => $_POST['assignee']);
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

include 'tpl.header.php';

echo '<h1>Create new issue</h1>';



// Choose project
echo '<h2>Project</h2>';

if ( !$project ) {
	$projects = jira_get('project', array(), $error, $info);
	echo '<ul>';
	foreach ( $projects AS $project ) {
		echo '<li>';
		echo '<a href="new.php?project=' . $project->id . '">' . $project->name . '</a>';
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
echo '<a href="new.php?project=' . $project->id . '">' . $project->name . '</a>';
echo ' (<a href="new.php">change</a>)';
echo '</li>';
echo '</ul>';



// Choose issue type
echo '<h2>Issue type</h2>';

if ( !$issuetype ) {
	echo '<ul>';
	foreach ( $project->issuetypes AS $issuetype ) {
		echo '<li>';
		echo '<a href="new.php?project=' . $project->id . '&issuetype=' . $issuetype->id . '">' . $issuetype->name . '</a>';
		echo '</li>';
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
echo ' (<a href="new.php?project=' . $project->id . '">change</a>)';
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
	<p>Summary: <input name="summary" /></p>
	<p>Description: <textarea name="description" rows="8"></textarea></p>
	<p>Priority: <select name="priority"><?= html_options($priorities, $defaultPriority) ?></select></p>
	<p>Assignee: <input name="assignee" value="<?= JIRA_USER ?>" /></p>

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
