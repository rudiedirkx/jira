<?php

$filterOptions = $user->filter_query_options;

?>

<div class="tab-links">
	<a href="#tab-page-filter">Filter</a>
	<a href="#tab-page-project">Project</a>
	<a href="#tab-page-query">Query</a>
</div>
<div class="tab-pages">
	<form action class="filter tab-page" id="tab-page-filter">
		<select name="query"><?= html_options($filterOptions, $query, '-- Filter') ?></select>
		<input type="submit" />
	</form>
	<form action class="filter tab-page" id="tab-page-project">
		<input name="project" value="<?= html(@$_GET['project']) ?>" placeholder="Project" />
		<input type="submit" />
	</form>
	<form action class="filter tab-page" id="tab-page-query">
		<input name="query" value="<?= html(@$query) ?>" />
		<input type="submit" />
	</form>
</div>

<script>
$('.tab-links a').on('click', function(e) {
	e.preventDefault();
	var $this = $(this),
		$links = $this.parent(),
		$pages = $links.next();
	$pages.children().addClass('hide').filter($this.attr('href')).removeClass('hide');
	$links.children().removeClass('active');
	$this.addClass('active');
}).first().trigger('click');
</script>
