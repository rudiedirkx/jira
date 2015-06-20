
<details>
	<summary>Jira requests</summary>
	<pre style="margin: 0"><?= implode('<br />', $jira_history) ?></pre>
</details>

<script>
$$('a[data-confirm]').on('click', function(e) {
	if ( !confirm(this.data('confirm')) ) {
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();
	}
});
$$('a.ajax:not(.ajaxed)').on('click', function(e) {
	e.preventDefault();
	document.body.addClass('loading');
	$.get(this.href).on('done', function(e, rsp) {
		if ( rsp.substr(0, 2) == 'OK' ) {
			location.reload();
		}
		else {
			document.body.removeClass('loading');
			alert(rsp);
		}
	});
}).addClass('ajaxed');
</script>

</body>

</html>
