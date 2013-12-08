
<script>
$$('a[data-confirm]').on('click', function(e) {
	if ( !confirm(this.data('confirm')) ) {
		e.preventDefault();
	}
});
</script>

</body>

</html>
