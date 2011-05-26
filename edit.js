var cmpr = CodeMirror.defaults;
cmpr.lineNumbers = true;
cmpr.tabindex = 0;
cmpr.mode = 'javascript';
cmpr.enterMode = 'keep';

$(document).ready(function() {
	var content_edit = document.getElementById('content_edit')
	var myCodeMirror = CodeMirror.fromTextArea(content_edit, cmpr);
	//myCodeMirror.setOption('mode', 'php');
});
