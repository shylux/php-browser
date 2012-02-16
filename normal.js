$(document).ready(function() {
/*
	$(".col_link").bind('click', function() {
		var that = this;
		setTimeout(function(){$(that).select();},10);
	});
*/
	$('.col_link').live('focus mouseup', function(e) {
		if (e.type == 'focusin') this.select();
		if (e.type == 'mouseup') return false;
	});
});
