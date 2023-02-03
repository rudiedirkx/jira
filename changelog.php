<?php

require 'inc.bootstrap.php';

do_logincheck();

$key = $_GET['key'];

$_title = "Changelog $key";
include 'tpl.header.php';
include 'tpl.epiccolors.php';

$issue = jira_get('issue/' . $key, array('expand' => 'changelog,transitions'), $error, $info);
if ( !$issue || $error ) {
	echo '<p>Invalid issue...</p>';
	echo '<pre>';
	var_dump($error);
	print_r($info);
	exit;
}
$issue = new Issue((array) $issue);

include 'tpl.issueheader.php';

?>
<style>
.changelog {
	width: 100%;
	border-collapse: collapse;
}
.changelog th,
.changelog td {
	text-align: left;
	vertical-align: top;
	border: solid 1px #ddd;
	padding: 3px 8px;
}
.changelog tr + tr.log th {
	border-top: solid 3px #ccc;
}
.changelog th .index {
	display: inline-block;
	width: 2em;
}
.changelog th .author,
.changelog th .datetime {
	font-weight: bold;
}
</style>
<?php

echo '<h2>' . $issue->changelog->total . ' changelog items</h2>';
echo '<table class="changelog">';
$i = $issue->changelog->total;
foreach ( $issue->changelog->histories as $log ) {
	echo '<tr class="log">';
	echo '<th colspan="3">';
	echo '<span class="index">' . ($i--) . '.</span>';
	echo ' ';
	echo '<span class="author">' . html($log->author->displayName) . '</span>';
	echo ' on ';
	echo '<span class="datetime">' . date(FORMAT_DATETIME, strtotime($log->created)) . '</span>';
	echo '</th>';
	echo '</tr>';
	foreach ($log->items as $item) {
		$big = mb_strlen($item->fromString ?? '') > 100 || mb_strlen($item->toString ?? '') > 100;

		echo '<tr class="item">';
		echo '<th class="field">' .ucfirst(html($item->field)) . '</th>';
		echo '<td class="from">' . ( $big ? mb_strlen($item->fromString) . ' chars' : nl2br(html($item->fromString)) ) . '</td>';
		echo '<td class="to">' . ( $big ? mb_strlen($item->toString) . ' chars' : nl2br(html($item->toString)) ) . '</td>';
		echo '<tr>';
	}
}
echo '</table>';

if ( isset($_GET['debug']) ) {
	$issue->clear();
	echo '<pre>' . print_r($issue, 1) . '</pre>';
}

include 'tpl.footer.php';
