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

/* Different Users
Superuser:	Has unlimited access to all functions. Protected by password.
User:		Has limited access defined in Config.
Stranger:	Don't have access. If you turn the login for User off the Stranger will be treated as a User.
*/

/* Upload
If you want to use the upload function you should modify the following entrys in php.ini (normally in /etc/php5/apache2/):

file_uploads = On (enable uploads)
upload_max_filesize = 1G (or 200M or whatever)
max_file_uploads = 1000 (number of files in one upload request)
post_max_size = 1G (same as upload_max_filesize)
max_execution_time = 300 (time available for upload in seconds. remeber that other may have slower connections than you)

****************************************/

class Config {
	// Config for User
	public $USER_ENABLED = true; // If you turn this off Strangers will be treated as Users!!
	public $USER_PASSWORD = 'asdffdsa';
	
	public $USER_PRISON = '.'; // This is the top directory for the user.

	public $USER_UPLOAD = false; // Is the user allowed to upload a file?
	public $USER_CREATE_DIR = true; // Is the user allowed to create a new directory?
	public $USER_DELETE = false; // Is the user allowed to delete a file or a directory?
	public $USER_EDIT = true; // Is the user allowed to edit a file?
	public $USER_BACKUP = false; // Is the user allowed to create a backup of a directoy?
	public $USER_RENAME = false; // Is the user allowed to rename a file/directory?
	
	// Superuser
	public $SUPERUSER_PASSWORD = 'roflroflnoobnoob';

	// Upload
	public $FORBIDDEN_EXTENSIONS = array('php');

	// Other
	public $CONTACT_EMAIL = 'shylux@gmail.com';
	public $CODEMIRROR_ENABLED = true; // Codemirror is the integrated editor.
	public $COOKIE_NAME = 'phpbrowser_password';

	function __construct() {
		$this->USER_PRISON = realpath($this->USER_PRISON);
	}
}

class PhpBrowser {
	private $config = null;
	private $isAdmin = false;
	private $message = null;
	private $path = null;

	function __construct($str_path) {
		$this->config = new Config();
		$path = (isset($str_path) && strlen($str_path) > 0) ? new JFile($str_path) : new JFile($this->config->USER_PRISON);
		if (!$path->exists()) $path = new JFile($this->config->USER_PRISON);
		$this->path = $path;
	}

	// Organisation
	function login() {
		if (isset($_REQUEST['browse_pw'])) {
			Util::setCookie($this->config->COOKIE_NAME, md5($_REQUEST['browse_pw']), 3);
		}
		if (isset($_COOKIE[$this->config->COOKIE_NAME])) {
			$pw = $_COOKIE[$this->config->COOKIE_NAME];
			if ($pw == md5($this->config->SUPERUSER_PASSWORD)) {
				$this->isAdmin = true;
				return true;
			} else if ($pw == md5($this->config->USER_PASSWORD)) {
				return true;
			}
		}
		if (!$this->config->USER_ENABLED) return true;
		$this->buildLogin();
		return false;
	}
	function logout() {
		Util::deleteCookie($this->config->COOKIE_NAME);
	}
        
        // Functions
  	function download() {
                if (!$this->path->isFile()) return;
                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename='.$this->path->getName());
                header('Content-Length: ' . $this->path->length());
                readfile($this->path);
  	}
	function createFile() {
		if (!isset($_REQUEST['filename'])) return;
		//$newfile = new JFile($this->path, $_REQUEST['filename']);
		$this->path->createNewFile($_REQUEST['filename']);
	}
	function createDirectory() {
		if (!isset($_REQUEST['dirname'])) return;
		$this->path->mkdir($_REQUEST['dirname']);
	}
	function delete() {
		$this->path->delete();
		if ($this->path->isDirectory()) $this->path = $this->path->getParent();
	} 

	// Build the page
	function buildBrowse() {
		$upperdir = ($this->path->isDirectory()) ? $this->path->getParent() : $this->path->getParent()->getParent();
		?>
		<div id=browser_list> <table id=browser_ttable>
			<tr><td><a href="?path=<?= $upperdir ?>"><?= $upperdir->getName() ?></a>
		<? foreach ($this->path->listFiles() as $file) {	?>
			<tr>
			<td>
			<? //Browse
                        if ($file->isDirectory()) { ?>
                        	<a href="?path=<?= $file ?>&action=browse"> <?= $file->getName() ?> </a>
                        <? } else {
                        	echo $file->getName();
                        } ?>	
                        </td><td>
                        <? //Download
                        if ($file->isFile()) { ?>
                        	<a href="?path=<?= $file ?>&action=download">Download</a>
                        <? } ?>
                        </td><td>
			<? //Edit
                        if ($file->isFile() && $file->canWrite()) { ?>
				<a href="?action=edit&path=<?= $file ?>">Edit</a>
			<? } ?>
			</td>
			<td>
			<? //Delete
                        if ($file->canWrite()) { ?>
				<a href='?action=delete&path=<?= $file ?>'>Delete</a>
			<? } ?>
			</td>
		<? } ?>
		<tr>
			<td colspan=2><form action="?action=cfile&path=<?= $this->path->getDirectory() ?>" method=POST>
				<input name=filename />
				<input type=submit value="Create File" />
			</form></td>
		</tr>
		<tr>
			<td colspan=2><form action="?action=cdir&path=<?= $this->path->getDirectory() ?>" method=POST>
				<input name=dirname />
				<input type=submit value="Create Directory" />
			</form></td>
		</tr>
		</table> </div>
		<div id=browser_upload>
			<form enctype="multipart/form-data" action="?action=upload&path=<?= $this->path->getDirectory(); ?>" method=POST>
			<label for=browser_upload>Upload a File</label>
			<input id=browser_upload name=files[] type=file multiple=true />
			<input type=submit value=Upload />
		</form>
		</div>
		<?
	}

	function buildEdit() { 
		if (!isset($_REQUEST['path']) || strlen($_REQUEST['path']) == 0) {$this->buildBrowse();return;}
		$file = new JFile($_REQUEST['path']);
		if (!$file->exists() || !$file->isFile()) {$this->buildBrowse();return;}
		?>
		<form action="?action=save&path=<?= $file ?>">
			<textarea name="browser_file_content"><?= $file->getContent() ?></textarea>
			<input type=submit value=Save />
		</form>
	<? }
	
	function buildLogin() { ?>
		Password protected area. </br>
		<form action='?action=login' type='REQUEST'>
			<input name='action' value='login' type='hidden'/>
			<input id=login_form_pw name='browse_pw' type='password' />
			<input name='path' value='<? if (isset($_REQUEST['path'])) echo $_REQUEST['path']?>' type='hidden'/>
			<input type='submit' value='Login' />
		</form>
	<? }
}

// Class for general tasks
class Util {
	public static function setCookie($key, $value, $expire_days) {
		setcookie($key, $value, time()+(86400*$expire_days));
		$_COOKIE[$key] = $value;
	}
	public static function deleteCookie($key) {
		setcookie($key);
		unset($_COOKIE[$key]);
	}
}

// File class to handle files and directorys
// Java File class acted as model
class JFile {
	// the absolute path including filename and extension
	public $path;

	public function __construct($path, $filename = "") {
		if (strlen($filename) > 0) $filename = DIRECTORY_SEPARATOR . $filename;
		$this->path = realpath((string)$path . (string)$filename);
	}
	public function __toString() {
		return $this->path;
	}

	// File properties
	public function canRead() {
		return is_readable($this->path);
	}
	public function canWrite() {
		return is_writable($this->path);
	}
	public function exists() {
		return file_exists($this->path);
	}
	public function isDirectory() {
		return is_dir($this->path);
	}
	public function isFile() {
		return is_file($this->path);
	}
	public function getName() {
		return pathinfo($this->path, PATHINFO_BASENAME);
	}
	// return a unix timestamp
	public function lastModified() {
		return filemtime($this->path);
	}
	// gets file size in bytes
	public function length() {
		return filesize($this->path);
	}
	public function getContent() {
		return file_get_contents($this->path);
	}

	// File modification
	public function createNewFile($filename) {
		if ($filename != null) return touch($this->path . DIRECTORY_SEPARATOR . $filename);
		return touch($this->path);
	}
	public function mkdir($dirname) {
		if ($dirname != null) return mkdir($this->path . DIRECTORY_SEPARATOR . $dirname);
		return mkdir($this->path);
	}
	public function renameTo(String $newpath) {
		return rename($this->path, $newpath);
	}
	public function delete() {
		if ($this->isFile()) return unlink($this);
		if ($this->isDirectory()) {
			foreach ($this->listFiles() as $file) {
				$file->delete();
			}
		}
	}

	// get other files
	public function getDirectory() {
		return ($this->isDirectory()) ? $this : $this->getParentFile();
	}
	public function getParent() {
		return new JFile(dirname($this->path));
	}
	public function getParentFile() {
		return $this->getParent();
	}
	public function listFiles() {
		$dir = $this->getDirectory();
		echo $dir;
		$files = scandir($dir); 
		$filearray = array();
		foreach ($files as $file) {
			if ($file == "." || $file == "..") continue;
			array_push($filearray, new JFile($dir, $file));
		}
		return $filearray;
	}
	
}

if (!isset($_REQUEST['path'])) $_REQUEST['path'] = null;
$phpbrowser = new PhpBrowser($_REQUEST['path']);
if (!isset($_REQUEST['action'])) $_REQUEST['action'] = null;

// download have to set header. so it have to be invoked before sending anything.
if ($_REQUEST['action'] == 'download') {
	if ($phpbrowser->login()) $phpbrowser->download();
}
                                                                   

?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <link rel="icon" href="favicon.ico" type="image/vnd.microsoft.icon" />
	<link rel="shortcut icon" href="[[favicon]]" type="image/x-icon" />
        <title>Browser</title>
</head>
<body>
<a href="?action=logout">Logout</a>
<?php
if ($phpbrowser->login()) {
	switch ($_REQUEST['action']) {
		case 'logout':
			$phpbrowser->logout();
			$phpbrowser->buildLogin();
			break;
		case 'edit':
			$phpbrowser->buildEdit();
			break;
		case 'cfile':
			$phpbrowser->createFile();
			$phpbrowser->buildBrowse();
			break;
		case 'cdir':
			$phpbrowser->createDirectory();
			$phpbrowser->buildBrowse();
			break;
		case 'delete':
			$phpbrowser->delete();
		case 'browse':
		default:
			$phpbrowser->buildBrowse();
			break;
	}
}
?>
</body>
</html>
