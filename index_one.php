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

//top dir for users
$prison_enabled = true;
$prison = "/var/www/php-browser/prison";

//Settings for normal users
$upload_enabled = false;
$createdir_enabled = false;
$delete_enabled = false;
$edit_enabled = false;

//password protection
$pw_protection = false;
$user_pw = "fu";

//admin has access to all features
$admin_pw = "lala";
$isadm = false;

//the used protocol
$protocol = (isset($_SERVER['HTTPS']))?'https://':'http://';

//remove extension at upload #array entrys must be lowercase
$cut_extension = true;
$cut_extension_array = array("php");

//Contact email
$contact_email = "shylux@gmail.com";

//CodeMirror
$codemirror_enabled = true;

/***************************************/

/*****************UPLOAD*****************
If you want to use the upload function you should modify the following entrys in php.ini (normally in /etc/php5/apache2/):

file_uploads = On (enable uploads)
upload_max_filesize = 1G (or 200M or whatever)
max_file_uploads = 1000 (number of files in one upload request)
post_max_size = 1G (same as upload_max_filesize)
max_execution_time = 300 (time available for upload in seconds. remeber that other may have slower connections than you)

****************************************/

//Check for Admin
if (isset($_COOKIE['browse_pw'])) if ($_COOKIE['browse_pw'] == $GLOBALS['admin_pw']) $GLOBALS['isadm'] = true;
//Check path
$prison = realpath($prison);
$_GET['path'] = (isset($_GET['path'])) ? realpath($_GET['path']) : $prison;
if (!$GLOBALS['isadm'] && $prison_enabled && !startsWith($_GET['path'], $prison, true)) $_GET['path'] = $prison;
//Check action
if (!isset($_GET['action'])) $_GET['action'] = "browse";

function main() {
	if ($_GET['action'] == "flogin") noaccess();
	if ($_GET['action'] == "logout") logout();
	if ($_GET['action'] == "login") login();
	checkpw();
	if (isset($_GET['msg'])) showmsg();
	if ($_GET['action'] == "download") download();
	if ($_GET['action'] == "createdir") createdir();
	if ($_GET['action'] == "createfile") createfile();
	if ($_GET['action'] == "Delete") delete();
	if ($_GET['action'] == "upload") upload();
	if ($_GET['action'] == "Edit") check_edit_redirect();
	if ($_GET['action'] == "save") save();
	echo $GLOBALS['head'];
	if ($_GET['action'] == "browse") browse();
	if ($_GET['action'] == "Edit") edit();
	echo "</body></html>";
}

function browse() {
	$dir_path = (isset($_GET['path']))? $_GET['path'] : $prison;
	if (!file_exists($dir_path)) $dir_path = $prison;
	if (is_file($dir_path)) $dir_path = dirname($dir_path);
	$main_dir = new MyFile($dir_path);
	$dir_handle;

	//Navigation Bar
	echo "Actual Dir: $main_dir<br/>";

	$list = $main_dir->listfiles();
	echo "<table>";

	//Check for top-level
	if ($GLOBALS['isadm'] || !$GLOBALS['prison_enabled'] || $main_dir != $GLOBALS['prison']) {
		//adds "Upper Directory" entry
		$up = new MyFile("");
		$up->name = dirname($main_dir);
		echo '<tr><td><img src="'.$GLOBALS['up_icon'].'" alt="" /></td><td><a class="browseup" href="' . $up->httplink_html() . '">Upper Directory</a></td></tr>';
	}

	//Print files
	foreach ($list as $i => $value) {
		echo($value->link());
	}
	//Folder is empty message
	if (count($list) == 0) echo '<tr><td></td></tr><tr id="item_empty"><td></td><td>Directory is empty!</td></tr><tr><td></td></tr>';

	//Create Directory and upload form
	echo $main_dir->createdir_form();
	echo $main_dir->createfile_form();
	echo "</table>";
	echo $main_dir->upload_form();

	//change http/https
	changeprotocol();
}
function check_edit_redirect() {
	if (!is_file($_GET['path'])) redirect("File doesn't exists or is a directory.");
	//if (!is_writable($_GET['path'])) redirect("File is not writable.");
}
function edit() {
	if ($GLOBALS['codemirror_enabled']) {
		echo '<script type="text/javascript">' . $GLOBALS['editjs'] . '</script>';
	}
	$f = new MyFile($_GET['path']);
	echo '<form id="edit_form" method="POST" action="'.phplink().'?action=save&amp;path='.$f.'">';
	echo '<div id="edit_info">Edit: '.$f."</div>";
	$dis = ($f->isWritable())?"":" disabled=true";
	echo '<input id="edit_save" type="submit" name="action" value="Save"'.$dis.' /><input id="edit_cancel" type="submit" name="action" value="Cancel"/>';
	echo ($f->isWritable())?"":"File is not writable.";
	echo '<textarea id="content_edit" name="content" rows="25"';
	echo ($f->isWritable())?' >':' readonly="readonly">';
	$c = file_get_contents($_GET['path']);
	echo htmlspecialchars($c);
	echo '</textarea>';
	echo '</form><br/>';
	echo '<input id="php" class="syn_change" type="submit" value="PHP" /><input id="javascript" class="syn_change" type="submit" value="Javascript" /><input id="htmlmixed" class="syn_change" type="submit" value="HTML" /><input id="css" class="syn_change" type="submit" value="CSS" /><input id="xml" class="syn_change" type="submit" value="XML" /><input id="text/x-csrc" class="syn_change" type="submit" value="C" /><input id="python" class="syn_change" type="submit" value="Python" />';
}
function save() {
	if ($_POST["action"] != "Save") redirect("Edit cancelled.");
	$filehandler = fopen($_GET['path'], 'w') or redirect("Can't open file for writing.");
	fwrite($filehandler, $_POST['content']);
	fclose($filehandler);
	redirect("File saved.");
}
function download() {
	download_file($_GET['path']);
	die();
}

function upload() {
	if (!$GLOBALS["upload_enabled"] && !$GLOBALS['isadm']) {
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

		//remove dangerous extensions
		if ($GLOBALS['cut_extension'] && !$GLOBALS['isadm']) {
			$ext = strtolower(end(explode('.', $dest)));
			if (in_array($ext, $GLOBALS['cut_extension_array'])) $dest = substr($dest, 0, strrpos($dest, '.'));
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
	//remove slashes
	$_GET['dirname'] = str_replace(array('/'), array(''), $_GET['dirname']);
	$newdirname = $_GET['path']. DIRECTORY_SEPARATOR . $_GET['dirname'];
	if (file_exists($newdirname)) redirect("Error: Entity already exists.");
	mkdir($newdirname);
	redirect("Directory created.");
}

//Create a File in the GET[path] with the given GET[filename]
function createfile() {
	if (!isset($_GET['filename'])) redirect();
	if (strlen($_GET['filename']) == 0) redirect("Please select the new name before submit.");
	//remove slashes
	$_GET['filename'] = str_replace(array('/'), array(''), $_GET['filename']);
	$newfilename = $_GET['path']. DIRECTORY_SEPARATOR . $_GET['filename'];
	if (file_exists($newfilename)) redirect("Error: Entity already exists.");
	touch($newfilename);
	redirect("File created.");
}

//Delete the File given in GET[path] !!! Be careful!
function delete() {
	if (!isset($_GET['del_path'])) redirect();
	if (!file_exists($_GET['del_path'])) redirect("Can't find File.");
	if (!is_writable($_GET['del_path'])) redirect("Not enough permission on server.");
	if (!is_dir($_GET['del_path'])) {
		if (!unlink($_GET['del_path'])) redirect("Unhandled Error.. Contact me: ".$GLOBALS["contact_email"]);
	} else {
		if(!delete_directory($_GET['del_path'])) redirect("Unhandled Error.. Contact me: ".$GLOBALS["contact_email"]);
	}
	redirect("Entity deleted.");
}
function delete_directory($dirname) {
	if (is_dir($dirname)) $dir_handle = opendir($dirname);
	if (!$dir_handle) return false;
	while($file = readdir($dir_handle)) {
		if ($file == "." || $file == "..") continue;
		var_dump($file);
		echo "<br/>";
		if (!is_dir($dirname."/".$file)) {
			unlink($dirname."/".$file);
		} else {
			delete_directory($dirname.'/'.$file);
		}
	}
	closedir($dir_handle);
	rmdir($dirname);
	return true;
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
	$GLOBALS['head'] .= $GLOBALS['login_form'];
	if (!$GLOBALS['pw_protection']) return;
	if (!isset($_COOKIE['browse_pw'])) noaccess();
	if ($_COOKIE['browse_pw'] != $GLOBALS['user_pw'] && $_COOKIE['browse_pw'] != $GLOBALS['admin_pw']) noaccess();
	setcookie_3d('browse_pw', $_COOKIE['browse_pw']);
	$GLOBALS['head'] .= $GLOBALS['logout_form'];
}
function login() {
	if (!isset($_GET['browse_pw'])) return;
	setcookie_3d('browse_pw', $_GET['browse_pw']);
	redirect();
}
function logout() {
	delcookie('browse_pw');	
	redirect();
}

//Displays a login screen
function noaccess() {
	echo $GLOBALS['head'] . "Password protected area.</br><form action='" . phplink() . "' type='GET'><input name='action' value='login' type='hidden'/><input id=login_form_pw name='browse_pw' type='password' /><input name='path' value='". $_GET['path'] ."' type='hidden'/><input type='submit' value='Login' /></form>";
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
	public function isWritable() {
		return (is_writable($this)) ? true : false;
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
		$f = ($this->isFile())?'file':'folder';
		$pic = ($this->isFile())?$GLOBALS['file_icon']:$GLOBALS['folder_icon'];
		$r = '<tr><td><img src="' . $pic . '" alt="'.$f.'" /></td><td><a class="browsedir" href="' . $this->httplink_html() . "\">" . $this->getName() . '</a></td>';
		//Delete Button
		if (($GLOBALS['delete_enabled'] || $GLOBALS['isadm']) && is_writable($this)) {
			$r.= '<td><form><input type="hidden" name="del_path" value="'.$this.'" /><input type="hidden" name="path" value="'.$this->parent().'" /><input type="submit" name="action" value="Delete" /></form></td>';
		} else {
			$r.='<td></td>';
		}
		if (($GLOBALS['edit_enabled'] || $GLOBALS['isadm']) && $this->isFile()) {
			$r.= '<td id="col_edit"><form><input type="hidden" name="path" value="'.$this.'" /><input type="submit" name="action" value="Edit" /></form></td>';
		}
		$r.="</tr>";
		return $r;
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
		if (!$GLOBALS["upload_enabled"] && !$GLOBALS['isadm']) return "Upload deactivated by User.";
		if (!is_writable($this)) return "Can't upload files because directory is not writable.";

		$html = $GLOBALS["uploadform"];
		$html = str_replace(array("[[uploadactiontarget]]"), array(MyFile::phplink()."?action=upload&amp;path=$this"), $html);
		return $html;
	}
	public function createdir_form() {
		if (!$GLOBALS["createdir_enabled"] && !$GLOBALS['isadm'] || !is_writable($this)) return;
		echo '<tr><form method="GET" action="'.$this->phplink().'"><td><img src="'.$GLOBALS['folder_icon'].'" alt="" /></td><td><input name="action" value="createdir" type="hidden" /><input name="path" value="'.$this.'" type="hidden"/><input name="dirname" type="text" /></td><td><input type="submit" value="Create Directory" /></td></form><td></td></tr>';
	}
	public function createfile_form() {
		if (!$GLOBALS["upload_enabled"] && !$GLOBALS['isadm'] || !is_writable($this)) return;
		echo '<tr><form method="GET" action="'.$this->phplink().'"><td><img src="'.$GLOBALS['file_icon'].'" alt="" /></td><td><input name="action" value="createfile" type="hidden" /><input name="path" value="'.$this.'" type="hidden"/><input name="filename" type="text" /></td><td><input type="submit" value="Create File" /></td></form><td></td></tr>';
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
	public function parent() {
		return dirname($this);	
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
function delcookie($key) {
	setcookie($key);
}

//Files
$head='<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" />        <link rel="icon" href="favicon.ico" type="image/vnd.microsoft.icon" /><link rel="shortcut icon" href="[[favicon]]" type="image/x-icon" />[[codemirror]]        <title>Browser</title></head><body>';
$css='* {font-family: "Courier New";font-size: 0.98em;padding: 0;margin: 0;}body {margin: 5px;}#msg {border-left: 2px solid red;padding-left: 2px;font-weight: bold;}#logout_form, #login_form {float: right;margin-left: 10px;}#login_form_pw {margin-right: 10px;}td img {height: 12px;}td {padding-right: 5px;height: 12px;}table {margin-left: 40px;margin-bottom: 20px;}td input {width: 95%;}#content_edit {clear: left;width: 100%;}#edit_cancel, #edit_save {float: left;}#col_edit {width: 100px;}input {border: none;border-left: 1px solid gray;border-right: 1px solid gray;padding: 1px 3px;}input:focus {background-color: #DEDEDE;}input:hover {background-color: #EBEBEB;}input:disabled {display: none;}#upload_start, #edit_cancel {margin: 0 10px;}#edit_form {width: 100%;}#edit_info {margin-right: 10px;margin-bottom: 10px;float: left;}.CodeMirror {clear: left;border: 1px solid black;height: auto;max-height: 800px;}.syn_change {margin-right: 10px;}';
$logout_form = '<form id="logout_form" action="'.phplink().'"><input type="hidden" name="action" value="logout" /><input type="submit" value="Logout" /></form>';
$login_form = '<form id="login_form" actin="'.phplink().'"><input type="hidden" name="action" value="flogin" /><input type="submit" value="Login" /></form>';
if ($codemirror_enabled) $editjs = '$(document).ready(function() {content_edit = document.getElementById(\'content_edit\');myCodeMirror = CodeMirror.fromTextArea(content_edit, getconf("text/stex"));$(\'.syn_change\').bind(\'click\', function(){myCodeMirror.setOption(\'mode\', $(this).attr(\'id\'));});});function getconf(mode) {cmpr = CodeMirror.defaults;cmpr.lineNumbers = true;cmpr.tabindex = 0;if (mode != undefined) if ($.inArray(mode, supported_modes)>-1) cmpr.mode = mode;cmpr.enterMode = \'keep\';if ($(\'#edit_save\').is(\':disabled\')) cmpr.readOnly = true;return cmpr;}var supported_modes = new Array("text/x-csrc", "javascript", "php", "css", "htmlmixed", "xml");/*c: text/x-csrcjavascript: javascriptphp: phpcss: csshtml: htmlmixedxml: xmllatex: text/stex*/';
$head .= '<style type="text/css">' . $css . '</style></head><body>';
if ($upload_enabled || $GLOBALS['isadm']) $uploadform='<form enctype="multipart/form-data" action="[[uploadactiontarget]]" method="POST">Upload a File:<input id="upload_select" name="files[]" type="file" multiple="true" /><input id="upload_start" type="submit" value="Start upload" /></form>';
$file_icon = (file_exists("file_icon.png"))? "file_icon.png" : "http://www.abload.de/img/file_icong8ie.png";
$folder_icon = (file_exists("folder_icon.png"))? "folder_icon.png" : "http://www.abload.de/img/folder_icon68fb.png";
$up_icon = (file_exists("up_icon.png"))? "up_icon.png" : "http://www.abload.de/img/up_iconrjhx.png";
$codemirror_head = ($codemirror_enabled) ? '<script type="text/javascript" src="http://code.jquery.com/jquery-1.6.js"></script><script src="CodeMirror2/lib/codemirror.js"></script><link rel="stylesheet" href="CodeMirror2/lib/codemirror.css"><script src="CodeMirror2/mode/javascript/javascript.js"></script><link rel="stylesheet" href="CodeMirror2/mode/javascript/javascript.css"><script src="CodeMirror2/mode/clike/clike.js"></script><link rel="stylesheet" href="CodeMirror2/mode/clike/clike.css"><script src="CodeMirror2/mode/css/css.js"></script><link rel="stylesheet" href="CodeMirror2/mode/css/css.css"><script src="CodeMirror2/mode/diff/diff.js"></script><link rel="stylesheet" href="CodeMirror2/mode/diff/diff.css"><script src="CodeMirror2/mode/haskell/haskell.js"></script><link rel="stylesheet" href="CodeMirror2/mode/haskell/haskell.css"><script src="CodeMirror2/mode/stex/stex.js"></script><link rel="stylesheet" href="CodeMirror2/mode/stex/stex.css"><script src="CodeMirror2/mode/xml/xml.js"></script><link rel="stylesheet" href="CodeMirror2/mode/xml/xml.css"><script src="CodeMirror2/mode/python/python.js"></script><link rel="stylesheet" href="CodeMirror2/mode/python/python.css"><script src="CodeMirror2/mode/htmlmixed/htmlmixed.js"></script><script src="CodeMirror2/mode/php/php.js"></script>' : '';
$codemirror_head = (file_exists('CodeMirror2')) ? $codemirror_head : str_replace('CodeMirror2', 'http://codemirror.net', $codemirror_head);
$head = str_replace(array('[[favicon]]', '[[codemirror]]'), array($folder_icon, $codemirror_head), $head);

//Start logic
main();
?>
