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
$prison = "/var/www/php-browser/prison";
$prison = "/var/www";

$upload_enabled = true;
$createdir_enabled = true;
$delete_enabled = true;
$edit_enabled = true;

//password protection
$pw_protection = true;
$user_pw = "testpassword";

//admin has access to all functions
$admin_pw = "l33t";
$isadm = false;

//the used protocol
$protocol = (isset($_SERVER['HTTPS']))?'https://':'http://';

//remove extension at upload #array entrys must be lowercase
$cut_extension = true;
$cut_extension_array = array("php");

//Contact email
$contact_email = "shylux@gmail.com";

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
	if ($main_dir != $GLOBALS['prison']) {
		//adds "Upper Directory" entry
		$up = new MyFile("");
		$up->name = dirname($main_dir);
		echo '<tr><td><img src="up_icon.png" alt="" /></td><td><a class="browseup" href="' . $up->httplink_html() . '">Upper Directory</a></td></tr>';
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
	echo "Edit: ".$_GET['path']."<br/><br/>";
	echo '<form method="POST" action="'.phplink().'?action=save&amp;path='.$_GET['path'].'">';
	echo '<textarea id="content_edit" name="content" rows="25"';
	echo (is_writable($_GET['path']))?' >':' readonly="readonly">';
	$c = file_get_contents($_GET['path']);
	echo htmlspecialchars($c);
	echo '</textarea><br/><input type="submit" name="action" value="Save" />';
	echo '<input id="edit_cancel" type="submit" name="action" value="Cancel"/></form>';
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
	if (!$GLOBALS["upload_enabled"] && !$isadm) {
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
		if ($GLOBALS['cut_extension']) {
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
	if ($_GET['del_path'] == $GLOBALS['prison']) redirect();
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
	if (!$GLOBALS['pw_protection']) return;
	if (!isset($_COOKIE['browse_pw'])) noaccess();
	if ($_COOKIE['browse_pw'] != $GLOBALS['user_pw']) noaccess();
	setcookie_3d('browse_pw', $_COOKIE['browse_pw']);
}
function login() {
	if (!isset($_GET['browse_pw'])) return;
	setcookie_3d('browse_pw', $_GET['browse_pw']);
	redirect();
}
//Displays a login screen
function noaccess() {
	echo $GLOBALS['head'] . "Password protected area.</br><form action='" . phplink() . "' type='GET'><input name='action' value='login' type='hidden'/><input name='browse_pw' type='password' /><input name='path' value='". $_GET['path'] ."' type='hidden'/><input type='submit' value='Login' /></form>";
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
		$f = ($this->isFile())?'file':'folder';
		$r = '<tr><td><img src="' . $f . '_icon.png" alt="'.$f.'" /></td><td><a class="browsedir" href="' . $this->httplink_html() . "\">" . $this->getName() . '</a></td>';
		//Delete Button
		if ($GLOBALS['delete_enabled'] && is_writable($this)) {
			$r.= '<td><form><input type="hidden" name="del_path" value="'.$this.'" /><input type="hidden" name="path" value="'.$this->parent().'" /><input type="submit" name="action" value="Delete" /></form></td>';
		} else {
			$r.='<td></td>';
		}
		if ($GLOBALS['edit_enabled'] && $this->isFile()) {
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
		if (!$GLOBALS["upload_enabled"] && !$isadm) return "Upload deactivated by User.";
		if (!is_writable($this)) return "Can't upload files because directory is not writable.";

		$html = $GLOBALS["uploadform"];
		$html = str_replace(array("[[uploadactiontarget]]"), array(MyFile::phplink()."?action=upload&amp;path=$this"), $html);
		return $html;
	}
	public function createdir_form() {
		if (!$GLOBALS["createdir_enabled"] || !is_writable($this)) return;
		echo '<tr><form method="GET" action="'.$this->phplink().'"><td><img src="folder_icon.png" alt="" /></td><td><input name="action" value="createdir" type="hidden" /><input name="path" value="'.$this.'" type="hidden"/><input name="dirname" type="text" /></td><td><input type="submit" value="Create Directory" /></td></form><td></td></tr>';
	}
	public function createfile_form() {
		if (!$GLOBALS["upload_enabled"] || !is_writable($this)) return;
		echo '<tr><form method="GET" action="'.$this->phplink().'"><td><img src="file_icon.png" alt="" /></td><td><input name="action" value="createfile" type="hidden" /><input name="path" value="'.$this.'" type="hidden"/><input name="filename" type="text" /></td><td><input type="submit" value="Create File" /></td></form><td></td></tr>';
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

//Files
$head="<!DOCTYPE html><html><head><meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\" />        <link rel=\"icon\" href=\"favicon.ico\" type=\"image/vnd.microsoft.icon\" /><link rel=\"shortcut icon\" href=\"folder_icon.png\" type=\"image/x-icon\" /><title>Browser</title>";
$css=file_get_contents("css.css");
$js=file_get_contents("func.js");
$head .= '<style type="text/css">' . $css . '</style><script type="text/javascript" src="http://code.jquery.com/jquery-1.6.js"></script><script type="text/javascript">' . $js . '</script></head><body>';
$uploadform=file_get_contents("uploadform");

//Start logic
main();
?>
