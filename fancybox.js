$(document).ready(function() {
	$(".fancybox").fancybox({
		prevEffect		: 'none',
		nextEffect		: 'none',
		closeBtn		: false,
                btnToggle: 'btnEnabled',
		helpers		: {
			title	: { type : 'inside' },
			buttons	: {}
		}
	});
});