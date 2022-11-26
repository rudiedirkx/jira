
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
		<button>Filter</button>
		<a href="filters.php">Your filters</a>
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-query">
		<div class="input">
			<input class="project-side manual-width" name="project" value="<?= html(@$_GET['project']) ?>" placeholder="Project..." />
			<input class="manual-width" name="query" value="<?= html(@$selectedQuery) ?>" />
		</div>
		<button>Filter</button>
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-project">
		<div class="input">
			<input name="project" value="<?= html(@$_GET['project'] ?: $user->index_project) ?>" placeholder="Project key..." />
		</div>
		<button>Filter</button>
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-search">
		<div class="input">
			<input class="project-side manual-width" name="project" value="<?= html(@$_GET['project']) ?>" placeholder="Project..." />
			<input class="manual-width" name="search" value="<?= html(@$_GET['search']) ?>" placeholder="Fulltext search tokens..." />
		</div>
		<button>Filter</button>
	</form>
	<form autocomplete="off" action class="filter tab-page" id="tab-page-goto">
		<div class="input">
			<input name="goto" value="" placeholder="Issue key, like ABCD-123..." />
		</div>
		<button>Filter</button>
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

	var key = makeUpKey(this.elements.goto);
	if ( key ) {
		this.elements.goto.value = key;
		setTimeout(function() {
			location = 'issue.php?key=' + key;
		}, 1);
	}
});
</script>
