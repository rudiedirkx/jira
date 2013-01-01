
<div class="tab-links">
	<a href="#tab-page-filter">Filter</a>
	<a href="#tab-page-query">Query</a>
	<a href="#tab-page-project">Project</a>
</div>
<div class="tab-pages">
	<form action class="filter tab-page" id="tab-page-filter">
		<select name="query"><?= html_options($filterOptions, $query, '-- Filter') ?></select>
		<input type="submit" />
		<a href="filters.php">Your filters</a>
	</form>
	<form action class="filter tab-page" id="tab-page-query">
		<input name="query" value="<?= html(@$query) ?>" />
		<input type="submit" />
	</form>
	<form action class="filter tab-page" id="tab-page-project">
		<input name="project" value="<?= html(@$_GET['project'] ?: $user->index_project) ?>" placeholder="Project" />
		<input type="submit" />
	</form>
</div>

<script>
var activeTab = '<?= $activeTab ?>', $tabLink;
var tabLinks = $('.tab-links a').on('click', function(e) {
	e.preventDefault();
	var $this = $(this),
		$links = $this.parent(),
		$pages = $links.next();
	$pages.children().addClass('hide').filter($this.attr('href')).removeClass('hide');
	$links.children().removeClass('active');
	$this.addClass('active');
});
if ( !activeTab || !($tabLink = $('.tab-links a[href="#tab-page-' + activeTab + '"]')).length ) {
	$tabLink = tabLinks.first();
}
$tabLink.trigger('click');
</script>
