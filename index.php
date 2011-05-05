<?php
/**********************************************
* Project:   PHP Browser                      *
* Date:      4 May 2011                       *
* Developer: Lukas Knöpfel alias Shylux       *
* Contact:   shylux@gmail.com                 *
**********************************************/
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
$createdir_enabled = true;


/***************************************/

//Path
$_GET['path'] = (isset($_GET['path'])) ? realpath($_GET['path']) : $prison;
if (!startsWith($_GET['path'], $prison, true)) $_GET['path'] = $prison;
if (!isset($_GET['action'])) $_GET['action'] = "browse";

function main() {
	if (isset($_GET['error'])) showerror();
	if ($_GET['action'] == "download") download();
	if ($_GET['action'] == "createdir") createdir();
	if ($_GET['action'] == "upload") upload();
	echo $GLOBALS['head'];
	if ($_GET['action'] == "browse") browse();
	echo "</body></html>";
}

function browse() {
	$dir_path = (isset($_GET['path']))? $_GET['path'] : "/var/www/";
	$main_dir = new MyFile($dir_path);
	$dir_handle;

	echo "Actual Dir: $main_dir<br/>";

	$list = $main_dir->listfiles();
	echo "<ul>";

	$upperdir = pathinfo($main_dir);
	$up = new MyFile("");
	$up->name = $upperdir['dirname'];
	echo '<li class="browseup_item"><a class="browseup" href="' . $up->httplink_html() . '">Upper Directory</a></li>';
	

	foreach ($list as $i => $value) {
		echo($value->link());
	}
	if (count($list) == 1) echo '<br/><li class="browseempty_item">Folder is empty!</li><br/>';
	echo $main_dir->createdir_form();
	echo "</ul>";
	echo $main_dir->upload_form();
}
function download() {
	download_file($_GET['path']);
	die();
}

function upload() {
	if (!$GLOBALS["upload_enabled"]) {
		showerror("Upload deactivated by User.");
	}

	$errstr = "";
	for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
		var_dump($_FILES['files']['name'][$i]);
		$dest = $_GET["path"] . DIRECTORY_SEPARATOR . $_FILES['files']['name'][$i];
		if (file_exists($dest)) {
			if (strlen($errstr) != 0) $errstr .= ", ";
			$errstr .= $_FILES['files']['name'][$i];
		}
		move_uploaded_file($_FILES["files"]["tmp_name"][$i], $dest);
	}
	if (strlen($errstr)!=0) redirect($errstr . " exists in target directory.");
	redirect("Upload successful");
}

//Create a Directory in the GET[path] with the given GET[dirname]
function createdir() {
	if (!isset($_GET['dirname'])) redirect();
	$newdirname = $_GET['path']. DIRECTORY_SEPARATOR . $_GET['dirname'];
	if (file_exists($newdirname)) redirect("Directory already exists.");
	mkdir($newdirname);
}

function showerror() {
	if (strlen($_GET['error']) == 0) return;
	echo '<div id="errormessage">'.$_GET['error'] ."</div><br/>";
}

//Redirect to the browse action and adds the $errormessage to the GET-Parameters
function redirect($errormessage) {
	header('Location: ' . phplink() . "?action=browse&path=" . $_GET['path'] . "&error=$errormessage");
	die();
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
			return '<li class="browsefile_item"><a class="browsefile" href="' . $this->httplink_html() . "\">" . $this->getName() . '</a></li>';
		} else {
			return '<li class="browsedir_item"><a class="browsedir" href="' . $this->httplink_html() . "\">" . $this->getName() . '</a></li>';
		}
	}
	public function httplink_html() {
		if ($this->isFile()) {
			return $this->phplink() . "?action=download&amp;path=$this";
		} else {
			return $this->phplink() . "?action=browse&amp;path=$this";
		}
	}
	public static function phplink() {
		return "http://" . $_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"];
	}
	public function upload_form() {
		if (!$GLOBALS["upload_enabled"]) return "Upload deactivated by User.";
		if (!is_writable($this)) return "Can't upload files because directory is not writable.";

		$html = $GLOBALS["uploadform"];
		$html = str_replace(array("[[uploadactiontarget]]"), array(MyFile::phplink()."?action=upload&amp;path=$this"), $html);
		return $html;
	}
	public function createdir_form() {
		if (!$GLOBALS["createdir_enabled"] || !is_writable($this)) return;
		echo '<li class="browsedir_item"><form method="GET" action="'.$this->phplink().'"><input name="action" value="createdir" type="hidden" /><input name="path" value="'.$this.'" type="hidden"/><input name="dirname" type="input" /><input type="submit" value="Create Directory" /></form></li>';
	}
	public function listfiles() {
		$dir_handle = @opendir($this);
		$counter = 0;
		$rearr = array();
		$rearrinfo = array();
		while (false !== ($file = readdir($dir_handle))) {
			if ($file == ".." || $file == ".") continue;
			$rearr[$counter] = $file;
			$counter++;
		}
		$counter = 0;
		natcasesort($rearr);
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
