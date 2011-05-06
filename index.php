<?php
/**********************************************
* Project:   PHP Browser                      *
* Date:      4 May 2011                       *
* Developer: Lukas Knöpfel alias Shylux       *
* Contact:   shylux@gmail.com                 *
**********************************************/
/*
           SHYLUX PUBLIC LICENSE
          Version 1, May 2011

Copyright (C) 2011 Lukas Knoepfel 
 Parkstrasse 28, 3700 Spiez, Switzerland
Everyone is permitted to copy and distribute verbatim or modified
copies of this license document, and changing it is allowed as long
as the name is changed.

           SHYLUX PUBLIC LICENSE
  TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

 0. Put the name of the developer on top of the code you copied.
 1. Mark the code you copied and add a copy or a link to this licence.
 2. Contact the developer so he can track his code.
*/

/*************CONFIGURATION*************/

$prison = "/var/www/";
$upload_enabled = true;
$createdir_enabled = true;
$pw_protection = true;
$pw = "testpassword";
$protocol = (isset($_SERVER['HTTPS']))?'https://':'http://';


/***************************************/

/*****************UPLOAD*****************
If you want to use the upload function you should modify the following entrys in php.ini (normally in /etc/php5/apache2/):

file_uploads = On (enable uploads)
upload_max_filesize = 1G (or 200M or whatever)
max_file_uploads = 1000 (number of files in one upload request)
post_max_size = 1G (same as upload_max_filesize)
max_execution_time = 300 (time available for upload in seconds. remeber that other may have slower connections than you)

****************************************/

//Set up vars
$prison = realpath($prison);
$_GET['path'] = (isset($_GET['path'])) ? realpath($_GET['path']) : $prison;
if (!startsWith($_GET['path'], $prison, true)) $_GET['path'] = $prison;
if (!isset($_GET['action'])) $_GET['action'] = "browse";

function main() {
	if ($_GET['action'] == "login") login();
	checkpw();
	if (isset($_GET['msg'])) showmsg();
	if ($_GET['action'] == "download") download();
	if ($_GET['action'] == "createdir") createdir();
	if ($_GET['action'] == "upload") upload();
	echo $GLOBALS['head'];
	if ($_GET['action'] == "browse") browse();
	echo "</body></html>";
}

function browse() {
	$dir_path = (isset($_GET['path']))? $_GET['path'] : $prison;
	if (!file_exists($dir_path)) $dir_path = $prison;
	$main_dir = new MyFile($dir_path);
	$dir_handle;

	echo "Actual Dir: $main_dir<br/>";

	$list = $main_dir->listfiles();
	echo "<ul>";

	//Check for top-level
	if ($main_dir != $GLOBALS['prison']) {
		//adds "Upper Directory" entry
		$up = new MyFile("");
		$up->name = dirname($main_dir);
		echo '<li class="browseup_item"><a class="browseup" href="' . $up->httplink_html() . '">Upper Directory</a></li>';
	}

	//Print files
	foreach ($list as $i => $value) {
		echo($value->link());
	}
	//Folder is empty message
	if (count($list) == 0) echo '<br/><li class="browseempty_item">Folder is empty!</li><br/>';

	//Create Directory and upload form
	echo $main_dir->createdir_form();
	echo "</ul>";
	echo $main_dir->upload_form();

	//change http/https
	changeprotocol();
}
function download() {
	download_file($_GET['path']);
	die();
}

function upload() {
	if (!$GLOBALS["upload_enabled"]) {
		redirect("Upload deactivated by User.");
	}

	$errstr = "";
	//loop throught uploaded files
	for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
		$dest = $_GET["path"] . DIRECTORY_SEPARATOR . $_FILES['files']['name'][$i];
		//check if file already exists
		if (file_exists($dest)) {
			if (strlen($errstr) != 0) $errstr .= ", ";
			$errstr .= $_FILES['files']['name'][$i];
		}
		move_uploaded_file($_FILES["files"]["tmp_name"][$i], $dest);
	}
	if (strlen($errstr)!=0) redirect("Error: " . $errstr . " exists in target directory.");
	redirect("Upload successful");
}

//Create a Directory in the GET[path] with the given GET[dirname]
function createdir() {
	if (!isset($_GET['dirname'])) redirect();
	if (strlen($_GET['dirname']) == 0) redirect("Please select the new name before submit.");
	$newdirname = $_GET['path']. DIRECTORY_SEPARATOR . $_GET['dirname'];
	if (file_exists($newdirname)) redirect("Error: Directory already exists.");
	mkdir($newdirname);
}

//Show a message on top
function showmsg() {
	if (strlen($_GET['msg']) == 0) return;
	echo '<div id="msg">'.$_GET['msg'] ."</div><br/>";
}

//Redirect to the browse action and adds the $msg to the GET-Parameters
function redirect($msg) {
	header('Location: ' . phplink() . "?action=browse&path=" . $_GET['path'] . "&msg=$msg");
	die();
}

function checkpw() {
	if (!$GLOBALS['pw_protection']) return;
	if (!isset($_COOKIE['browse_pw'])) noaccess();
	if ($_COOKIE['browse_pw'] != $GLOBALS['pw']) noaccess();
	setcookie_3d('browse_pw', $_COOKIE['browse_pw']);
}
function login() {
	if (!isset($_GET['browse_pw'])) return;
	setcookie_3d('browse_pw', $_GET['browse_pw']);
	redirect();
}
//Displays a login screen
function noaccess() {
	echo $GLOBALS['head'] . "Password protected area.</br><form action='" . phplink() . "' type='GET'><input name='action' value='login' type='hidden'/><input name='browse_pw' type='password' /><input type='submit' value='Login' /></form>";
	changeprotocol();
	die();
}
//Change between http and https
function changeprotocol() {
	$otherp = (isset($_SERVER['HTTPS']))?"http":"https";
	echo "<br/>Change Protocol: <a id='pswitcher' href='$otherp://" . $_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"] . "?action=browse'>$otherp</a>";
}

//File class
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
		return $GLOBALS['protocol'] . $_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"];
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
//Show the content of a file on the Webpage (i don't use this anymore since download)
function echo_file($path) {
	$content = file_get_contents($path);
        $spec = htmlspecialchars($content);
        $out = str_replace(array("\n"), array("<br/>"), $spec);
        echo $out;	
}
function phplink() {
	return $GLOBALS['protocol'] . $_SERVER["SERVER_NAME"] . $_SERVER["SCRIPT_NAME"];
}
function setcookie_3d($key, $value) {
	setcookie($key, $value, time()+(86400 * 3)); //86400 = one day
}

//Files
$head="<!DOCTYPE html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\" />        <link rel=\"icon\" href=\"favicon.ico\" type=\"image/vnd.microsoft.icon\" /><link rel=\"shortcut icon\" href=\"folder_icon.png\" type=\"image/x-icon\" /><title>Browser</title>";
$css="* {font-family: \"Arial\";font-size: 0.98em;}.browsefile_item {list-style-image: url(file_icon.png);}.browsedir_item {list-style-image: url(folder_icon.png);}.browseempty_item {list-style-type: none;}.browseup_item {list-style-image: url(up_icon.png);}#msg {border-left: 2px solid red;padding-left: 2px;font-weight: bold;}";
$head .= '<style type="text/css">' . $css . '</style></head><body>';
$uploadform="<form enctype=\"multipart/form-data\" action=\"[[uploadactiontarget]]\" method=\"POST\">Upload a File:<input name=\"files[]\" type=\"file\" multiple=\"true\" /><input type=\"submit\" value=\"Start upload\" /></form>";

//Start logic
main();
?>
