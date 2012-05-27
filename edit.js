$(document).ready(function() {
	$('.nojs').removeClass('nojs');

	var defaultconf;
	switch ($('#edit_filename').html().split('.').pop()) {
		case 'js':
			defaultconf = 'javascript';
			break;
		case 'css':
			defaultconf = 'css';
			break;
		case 'html':
			defaultconf = 'htmlmixed';
			break;
		case 'xml':
			defaultconf = 'xml';
			break;
		case 'c':
			defaultconf = 'text/x-csrc';
			break;
		case 'php':
		default:
			defaultconf = 'php';
	}

	var editor = CodeMirror.fromTextArea($("#code")[0], getconf(defaultconf));

	$('#syntax_modes div').bind('click', function(){
		$(".selected").removeClass("selected");
		$(this).addClass("selected");
		editor.setOption('mode', $(this).attr('mode'));
	});
});

function getconf(mode) {
	$(".selected").removeClass("selected");
	$('div[mode="'+mode+'"]').addClass("selected");

	cmpr = CodeMirror.defaults;
	cmpr.lineNumbers = true;
	cmpr.matchBrackets = true;
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
