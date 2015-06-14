
<div class="tab-links">
	<a href="#tab-page-filter">Filter</a>
	<a href="#tab-page-query">Query</a>
	<a href="#tab-page-project">Project</a>
	<a href="#tab-page-search">Search</a>
	<a href="#tab-page-goto">Go to</a>
</div>
<div class="tab-pages">
	<form autocomplete="off" action class="filter tab-page" id="tab-page-filter">
		<select name="query"><?= html_options($filterOptions, $query, '-- Filter') ?></select>
		<input type="submit" />
		<a href="filters.php">Your filters</a>
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-query">
		<input name="query" value="<?= html(@$query) ?>" />
		<input type="submit" />
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-project">
		<input name="project" value="<?= html(@$_GET['project'] ?: $user->index_project) ?>" placeholder="Project key..." />
		<input type="submit" />
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-search">
		<input name="search" value="<?= html(@$_GET['search']) ?>" placeholder="Fulltext search tokens..." />
		<input type="submit" />
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-goto">
		<input name="goto" value="" placeholder="Issue key, like ABCD-123..." />
		<input type="submit" />
	</form>
</div>

<script>
var activeTab = '<?= $activeTab ?>', tabLink;
var tabLinks = $$('.tab-links a').on('click', function(e) {
	e.preventDefault();
	var links = this.getParent(),
		pages = links.getNext();
	pages.getChildren().addClass('hide').filter(this.attr('href')).removeClass('hide');
	links.getChildren().removeClass('active');
	this.addClass('active');
});
if ( !activeTab || !(tabLink = $('.tab-links a[href="#tab-page-' + activeTab + '"]', 1)) ) {
	tabLink = tabLinks[0];
}
tabLink.fire('click');

$('tab-page-goto').on('submit', function(e) {
	e.preventDefault();

	var key = this.elements.goto.value.trim().toUpperCase();
	location = 'issue.php?key=' + encodeURIComponent(key);
});
</script>
