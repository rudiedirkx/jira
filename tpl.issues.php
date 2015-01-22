<?php

$fieldsmeta = $user->custom_field_ids;

echo '<div class="menu">';
echo '	<h2 class="pre-menu">Showing ' . count($issues->issues) . ' of ' . $issues->total . '</h2>';
echo '	(<a href="new.php">create</a>)';
echo '</div>';

echo '<hr>';

foreach ( $issues->issues AS $issue ) {
	$fields = $issue->fields;

	$resolution = '';
	if ( $fields->resolution ) {
		$resolution = ': ' . html($fields->resolution->name);
	}

	$status = $fields->resolution ? $fields->resolution->name : $fields->status->name;

	$created = strtotime($fields->created);
	$updated = strtotime($fields->updated);

	$storypoints = @$fieldsmeta['story points'] && @$fields->{$fieldsmeta['story points']} ? ' (' . @$fields->{$fieldsmeta['story points']} . ' pt)' : '';

	echo '<h2><a href="issue.php?key=' . $issue->key . '">' . $issue->key . ' ' . html($fields->summary) . '</a>' . $storypoints . '</h2>';
	echo '<p class="short-meta">';
	echo '	<span class="left">' . html_icon($fields->issuetype, 'issuetype') . ' ' . html($fields->issuetype->name) . '</span>';
	echo '	<span class="center">' . ( @$fields->priority ? html_icon($fields->priority, 'priority') . ' ' . html($fields->priority->name) : '&nbsp;' ) . '</span>';
	echo '	<span class="right"><strong>' . html($status) . '</strong></span>';
	echo '</p>';
	if ( $fields->labels ) {
		echo '<p class="labels">Labels: <span class="label">' . implode('</span> <span class="label">', array_map('html', $fields->labels)) . '</span></p>';
	}
	echo '<p class="dates">';
	echo '	<span class="left">' . date(FORMAT_DATETIME, $created) . '</span>';
	echo '	<span class="right">' . date(FORMAT_DATETIME, $updated) . '</span>';
	echo '</p>';

	echo '<hr>';
}

?>

<p id="pager">
	<a href="?<?= html_q(array('page' => $page-1)) ?>">&lt; prev</a> |
	<span><?= $page+1 ?> / <?= ceil($issues->total/$perPage) ?> (<?= $issues->total ?>)</span> |
	<a href="?<?= html_q(array('page' => $page+1)) ?>">next &gt;</a>
</p>
