<?php

require 'inc.bootstrap.php';

do_logincheck();

if ( isset($_GET['project'], $_GET['issuetype'], $_POST['summary'], $_POST['description'], $_POST['priority']) ) {
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

include 'tpl.header.php';

$meta = jira_get('issue/createmeta', array('expand' => 'projects.issuetypes.fields'), $error, $info);

echo '<h1>Create new issue</h1>';

// Choose project
echo '<h2>Project</h2>';
echo '<ul>';
foreach ( $meta->projects AS $project ) {
	$active = @$_GET['project'] == $project->id ? 'active' : '';
	echo '<li><a class="' . $active . '" href="new.php?project=' . $project->id . '">' . $project->name . '</a></li>';
}
echo '</ul>';

// Have project
if ( !empty($_GET['project']) && ($project = get_project($meta->projects, $_GET['project'])) ) {
	// print_r($project);

	// Choose issue type
	echo '<h2>Issue type</h2>';
	echo '<ul>';
	foreach ( $project->issuetypes AS $issuetype ) {
		$active = @$_GET['issuetype'] == $issuetype->id ? 'active' : '';
		echo '<li><a class="' . $active . '" href="new.php?project=' . $project->id . '&issuetype=' . $issuetype->id . '">' . $issuetype->name . '</a></li>';
	}
	echo '</ul>';

	// Have issue type
	if ( !empty($_GET['issuetype']) && ($issuetype = get_issuetype($project->issuetypes, $_GET['issuetype'])) ) {
		$priorities = array();
		foreach ( $issuetype->fields->priority->allowedValues AS $priority ) {
			$priorities[$priority->id] = $priority->name;
		}
		$priorityKeys = array_keys($priorities);
		$defaultPriority = $priorityKeys[ ceil((count($priorities)-1)/2) ];

		?>
<form autocomplete="off" action method="post">
	<p>Summary: <input name="summary" /></p>
	<p>Description: <textarea name="description" rows="8"></textarea></p>
	<p>Priority: <select name="priority"><?= html_options($priorities, $defaultPriority) ?></select></p>
	<p>Assignee: <input name="assignee" /></p>

	<p><input type="submit" /></p>
</form>
		<?php
	}
}

// echo '<pre>';
// print_r($meta);

include 'tpl.footer.php';



function get_project( $projects, $is_project ) {
	foreach ( $projects AS $project ) {
		if ( $project->id == $is_project ) {
			return $project;
		}
	}
}

function get_issuetype( $issuetypes, $is_issuetype ) {
	foreach ( $issuetypes AS $issuetype ) {
		if ( $issuetype->id == $is_issuetype ) {
			return $issuetype;
		}
	}
}
