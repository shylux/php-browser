<?php
/*
           DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
                   Version 2, December 2004

Copyright (C) 2004 Sam Hocevar
 14 rue de Plaisance, 75014 Paris, France
Everyone is permitted to copy and distribute verbatim or modified
copies of this license document, and changing it is allowed as long
as the name is changed.

           DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
  TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

 0. You just DO WHAT THE FUCK YOU WANT TO.
*/

/*************CONFIGURATION*************/

$prison = "/var/www/";
$upload_enabled = true;

/***************************************/

//Path
$_GET['path'] = (isset($_GET['path'])) ? realpath($_GET['path']) : $prison;
if (!startsWith($_GET['path'], $prison, true)) $_GET['path'] = $prison;
if (!isset($_GET['action'])) $_GET['action'] = "browse";

function main() {
	if ($_GET['action'] == "download") download();
	echo $GLOBALS['head'];
	if ($_GET['action'] == "browse") browse();
	if ($_GET['action'] == "upload") upload();
	if ($_GET['action'] == "createdir") {
					createdir();
					browse();}
	echo "</body></html>";
}

function browse() {
	$dir_path = (isset($_GET['path']))? $_GET['path'] : "/var/www/";
	$main_dir = new MyFile($dir_path);
	$dir_handle;

	echo "Actual Dir: $main_dir<br/>";

	$list = $main_dir->listfiles();
	echo "<ul>";
	foreach ($list as $i => $value) {
		echo($value->link());
	}
	echo "</ul>";
	echo $main_dir->uploadform();
}
function download() {
	download_file($_GET['path']);
	die();
}

function upload() {
	if (!$GLOBALS["upload_enabled"]) {
		echo "Upload deactivated by User.";
		return;
	}

	for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
		var_dump($_FILES['files']['name'][$i]);
	}

	$dest = $_GET["path"].DIRECTORY_SEPARATOR.$_FILES["uploadfile"]["name"];
	if (file_exists($dest)) {
		echo "$dest already exists.";
	} else {
		move_uploaded_file($_FILES["uploadfile"]["tmp_name"], $dest);
		echo "Successful uploaded $dest";
	}
}

function createdir() {
	if (!isset($_GET['dirname'])) return;
	echo "Create dir: " . $_GET['dirname'] . "<br/>";
}

class MyFile {
	public $path = null;
	public $name = null;
	public $extension = null;
	public function __construct($whole_path) {
		$info = pathinfo($whole_path);
		$this->name = $info['filename'];
		if (isset($info['extension'])) $this->extension = $info['extension'];
		if (isset($info['dirname'])) $this->path = $info['dirname'];
	}
	public function __toString() {
		return $this->toString();
	}
	public function toString() {
		$re = "";
                if ($this->path != null) $re .= $this->path . DIRECTORY_SEPARATOR;
                if ($this->name != null) $re .= $this->name;
                if ($this->extension != null) $re .= "." . $this->extension;
		$re = realpath($re);
                return $re;
	} 
	public function isFile() {
		return (is_file($this)) ? true : false;
	}
	public function isDir() {
		return !$this->isFile();
	}
	public function isHidden() {
		return ($this->name == null) ? true : false;
	}
	public function getName() {
		if ($this->isHidden()) {
			return "." . $this->extension;
		} else {
			if ($this->isDir() || $this->extension == "") {
				return $this->name;
			} else {
				return $this->name . "." . $this->extension;
			}
		}
	}
	public function link() {
		if ($this->isFile()) {
			return "<li class=\"browsefile_item\"><a class=\"browsefile\" href=\"" . $this->phplink() . "?action=download&amp;path=$this\">" . $this->getName() . "</a></li>";
		} else {
			return "<li class=\"browsedir_item\"><a class=\"browsedir\" href=\"" . $this->phplink() . "?action=browse&amp;path=$this\">" . $this->getName() . "</a></li>";
		}
	}
	public static function phplink() {
		return "http://" . $_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"];
	}
	public function uploadform() {
		if (!$GLOBALS["upload_enabled"]) return "Upload deactivated by User.";
		if (!is_writable($this)) return "Can't upload files because directory is not writable.";

		$html = $GLOBALS["uploadform"];
		$html = str_replace(array("[[uploadactiontarget]]"), array(MyFile::phplink()."?action=upload&amp;path=$this"), $html);
		return $html;
	}
	public function listfiles() {
		$dir_handle = @opendir($this);
		$counter = 1;
		$rearr = array();
		$rearrinfo = array();
		$upperdir = pathinfo($this);
		while (false !== ($file = readdir($dir_handle))) {
			if ($file == ".." || $file == ".") continue;
			$rearr[$counter] = $file;
			$counter += 1;
		}
		$counter = 1;
		natcasesort($rearr);
		$rearrinfo[0] = new MyFile("");
		$rearrinfo[0]->name = $upperdir['dirname'];
		foreach ($rearr as $i => $value) {
			$rearrinfo[$counter] = new MyFile($this . DIRECTORY_SEPARATOR . $value);
			$counter += 1;
		}
		return $rearrinfo;
	}
	public function __get($att) {
		return $this->$att;
	}
}
function download_file($path) {
	$path = realpath($path);
	//if (file_exists($path)) die("Target $path do not exists!");
	if (is_dir($path)) die("Target is a directory!");
	header('Content-Description: File Transfer');
	header('Content-Disposition: attachment; filename='.basename($path));
	header('Content-Length: ' . filesize($path));
	readfile($path);
}
function startsWith($haystack,$needle,$case=true) {
	if($case){return (strcmp(substr($haystack, 0, strlen($needle)),$needle)===0);}
	return (strcasecmp(substr($haystack, 0, strlen($needle)),$needle)===0);
}
function echo_file($path) {
	$content = file_get_contents($path);
        $spec = htmlspecialchars($content);
        $out = str_replace(array("\n"), array("<br/>"), $spec);
        echo $out;	
}
function phplink() {
	return "http://" . $_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"];
}

//Files
//$uploadform="<form enctype=\"multipart/form-data\" action=\"[[uploadactiontarget]]\" method=\"POST\">Upload a File:<input name=\"uploadfile\" type=\"file\" /><input type=\"submit\" value=\"Start upload\" /></form>";
//$head="<!DOCTYPE html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\" />        <link rel=\"icon\" href=\"favicon.ico\" type=\"image/vnd.microsoft.icon\" /><link rel=\"shortcut icon\" href=\"folder_icon.png\" type=\"image/x-icon\" /><link rel=\"stylesheet\" type=\"text/css\" href=\"css.css\" />        <title>Browser</title></head><body>";
$uploadform = file_get_contents("uploadform");
$head = file_get_contents("head");

//Start logic
main();
?>
