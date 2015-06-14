<?php

$epicColors = array(
	array('red', 'white'),
	array('green', 'white'),
	array('blue', 'white'),
	array('yellow', 'black'),
	array('orange', 'black'),
	array('purple', 'white'),
	array('pink', 'black'),
	array('lime', 'white'),
	array('lightblue', 'white'),
);

?>
<style>
<? foreach ($epicColors as $i => $class): ?>
.epic.ghx-label-<?= $i+1 ?> {
	background-color: <?= $epicColors[$i][0] ?>;
	color: <?= $epicColors[$i][1] ?>;
}
<? endforeach ?>
</style>
