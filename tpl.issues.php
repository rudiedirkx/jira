<?php

echo '<div class="menu">';
echo '	<h2 class="pre-menu">Showing ' . count($issues->issues) . ' of ?</h2>';
echo '	(<a href="new.php">create</a>)';
echo '</div>';

echo '<hr>';

foreach ( $issues->issues AS $issue ) {
	$fields = $issue->fields;

	$status = $fields->resolution ? $fields->resolution->name : $fields->status->name;

	$storypoints = $issue->story_points ? ' (' . $issue->story_points . ' pt)' : '';

	echo '<h2><a href="issue.php?key=' . $issue->key . '">' . $issue->key . ' ' . html(trim($fields->summary)) . '</a>' . $storypoints . '</h2>';
	echo '<p class="short-meta">';
	echo '	<span class="left">' . html_icon($fields->issuetype, 'issuetype') . ' ' . html($fields->issuetype->name) . '</span>';
	echo '	<span class="center">' . ( @$fields->priority ? html_icon($fields->priority, 'priority') . ' ' . html($fields->priority->name) : '&nbsp;' ) . '</span>';
	echo '	<span class="right"><strong>' . html_icon($fields->status) . ' ' . html($status) . '</strong></span>';
	echo '</p>';
	if ( $fields->labels ) {
		echo '<p class="labels">Labels: ' . html_labels($fields->labels) . '</p>';
	}
	echo '<p class="dates">';
	echo '	<span class="left">' . date(FORMAT_DATETIME, $issue->created) . '</span>';
	echo '	<span class="right">' . date(FORMAT_DATETIME, $issue->updated) . '</span>';
	echo '</p>';

	echo '<hr />';
}

?>

<p id="pager">
	<? if ($issues->nextPageToken): ?>
		<a href="<?= html_q(array('nextPageToken' => $issues->nextPageToken)) ?>">next</a>
	<? endif ?>
</p>
