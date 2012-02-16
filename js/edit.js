$(document).ready(function() {
	content_edit = document.getElementById('content_edit');
	myCodeMirror = CodeMirror.fromTextArea(content_edit, getconf("text/stex"));
	$('.syn_change').bind('click', function(){
		myCodeMirror.setOption('mode', $(this).attr('id'));
	});
});

function getconf(mode) {
	cmpr = CodeMirror.defaults;
	cmpr.lineNumbers = true;
	cmpr.tabindex = 0;
	if (mode != undefined) if ($.inArray(mode, supported_modes)>-1) cmpr.mode = mode;
	
	cmpr.enterMode = 'keep';
	if ($('#edit_save').is(':disabled')) cmpr.readOnly = true;
	return cmpr;
}

var supported_modes = new Array("text/x-csrc", "javascript", "php", "css", "htmlmixed", "xml");

/*

c: text/x-csrc
javascript: javascript
php: php
css: css
html: htmlmixed
xml: xml
latex: text/stex
*/
