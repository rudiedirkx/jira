
<div class="tab-links">
	<a href="#tab-page-filter">Filter</a>
	<a href="#tab-page-query">Query</a>
	<a href="#tab-page-project">Project</a>
	<a href="#tab-page-search">Search</a>
	<a href="#tab-page-goto">Go to</a>
</div>
<div class="tab-pages">
	<form autocomplete="off" action class="filter tab-page" id="tab-page-filter">
		<div class="input">
			<input class="project-side manual-width" name="project" value="<?= html(@$_GET['project']) ?>" placeholder="Project..." />
			<select class="manual-width" name="query"><?= html_options($filterOptions, $selectedQuery, '-- Filter') ?></select>
		</div>
		<input type="submit" />
		<a href="filters.php">Your filters</a>
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-query">
		<div class="input">
			<input class="project-side manual-width" name="project" value="<?= html(@$_GET['project']) ?>" placeholder="Project..." />
			<input class="manual-width" name="query" value="<?= html(@$selectedQuery) ?>" />
		</div>
		<input type="submit" />
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-project">
		<div class="input">
			<input name="project" value="<?= html(@$_GET['project'] ?: $user->index_project) ?>" placeholder="Project key..." />
		</div>
		<input type="submit" />
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-search">
		<div class="input">
			<input class="project-side manual-width" name="project" value="<?= html(@$_GET['project']) ?>" placeholder="Project..." />
			<input class="manual-width" name="search" value="<?= html(@$_GET['search']) ?>" placeholder="Fulltext search tokens..." />
		</div>
		<input type="submit" />
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-goto">
		<div class="input">
			<input name="goto" value="" placeholder="Issue key, like ABCD-123..." />
		</div>
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

	var key = this.elements.goto.value.trim(); // issue 17 | issue17
	key = key.replace(/\s+/, '-').toUpperCase(); // ISSUE-17 | ISSUE17
	key = key.replace(/^([A-Z_]+)(\d+)$/, '$1-$2'); // ISSUE-17

	// Jira's keys are very simple. If it's not that, no need to redirect.
	if ( !/^[A-Z_]+\-\d+$/.test(key) ) {
		alert('Invalid key: ' + key);
		return;
	}

	this.elements.goto.value = key;
	location = 'issue.php?key=' + key;
});
</script>
