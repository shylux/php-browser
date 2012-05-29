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

include_once('jfile_php.php');

class Config {
	// Config for User
	public $USER_ENABLED = true; // If you turn this off Strangers will be treated as Users!!
	public $USER_PASSWORD = 'asdffdsa';
	
	public $USER_PRISON = '.'; // This is the top directory for the user.

	public $USER_UPLOAD = false; // Is the user allowed to upload a file?
	public $USER_CREATE_DIR = true; // Is the user allowed to create a new directory?
	public $USER_DELETE = false; // Is the user allowed to delete a file or a directory?
	public $USER_EDIT = true; // Is the user allowed to edit a file?
	public $USER_BACKUP = false; // TODO:YYYYYhe user allowed to create a backup of a directoy?
	public $USER_RENAME = false; // Is the user allowed to rename a file/directory?
	
	// Superuser
	public $SUPERUSER_PASSWORD = 'fdsaasdf';

	// Upload
	public $FORBIDDEN_EXTENSIONS = array('php');

	// Other
	public $CONTACT_EMAIL = 'shylux@gmail.com';
	public $CODEMIRROR_ENABLED = false; // Codemirror is the integrated editor.
	public $COOKIE_NAME = 'phpbrowser_password';

	public $WEBSERVER_ROOT = '/var/www';

	function __construct() {
		$this->USER_PRISON = realpath($this->USER_PRISON);
	}
}

class PhpBrowser {
	private $config = null;
	private $isAdmin = false;
	private $message = null;
	private $path = null;
	private $path_changed = false;

	function __construct($str_path) {
		$this->config = new Config();
		$this->login();
		// check path
		if (
			isset($str_path) &&
			strlen($str_path) > 0 &&
			( strpos($str_path, $this->config->USER_PRISON) === 0 || $this->isAdmin )
		) {
			$path = new JFile($str_path);
			if (!$path->exists()) {
				$this->path_changed = true;
				$path = new JFile($this->config->USER_PRISON);
			}
		} else {
			$path = new JFile($this->config->USER_PRISON);
		}
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
		//$this->buildLogin();
		return false;
	}
	function logout() {
		Util::deleteCookie($this->config->COOKIE_NAME);
	}
        
        // Functions
  	function download() {
                if ($this->path_changed || !$this->path->isFile()) return;
                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename='.$this->path->getName());
                header('Content-Length: ' . $this->path->length());
                readfile($this->path);
  	}
	function createFile() {
		if ($this->path_changed || !isset($_REQUEST['filename'])) return;
		//$newfile = new JFile($this->path, $_REQUEST['filename']);
		$this->path->createNewFile($_REQUEST['filename']);
	}
	function createDirectory() {
		if ($this->path_changed || !isset($_REQUEST['dirname'])) return;
		$this->path->mkdir($_REQUEST['dirname']);
	}
	function delete() {
		if ($this->path_changed) return;
		$tmppath = $this->path;
		if ($this->path->isDirectory()) {
			$this->path = $this->path->getParent();
		} else {
			$this->path = $this->path->getDirectory();
		}
		$tmppath->delete();
	} 

	// Build the page
	function buildBrowse() {
		$upperdir = ($this->path->isDirectory()) ? $this->path->getParent() : $this->path->getParent()->getParent();
		?>
		<div id=browser_list> <table id=browser_ttable>
			<tr>
				<td><img src="up_icon.png" /></td>
				<td colspan="100%">
	                        	<a class="directory" href="?path=<?= $upperdir ?>">Upper Directory</a>
				</td>
			</tr>
		<?
		$filelist = $this->path->listFiles();
		if (count($filelist) == 0) { ?>
			<tr><td colspan="100%"><b>No Files or Directorys</b></td></tr>
		<? }
		// diesplay files and directorys
		foreach ($this->path->listFiles() as $file) {	?>
			<tr>
			<td>
			<img src="<?=($file->isFile()) ? "file_icon.png" : "folder_icon.png" ?>" />
			<? //show read/write/execute
			/*
			echo ($file->canRead()) ? "<b>r</b>" : "r";
			echo ($file->canWrite()) ? "<b>w</b>" : "w";
			echo ($file->canExecute()) ? "<b>x</b>" : "x";
			*/
			?>
			</td><td class="filename">
			<? //Browse no action needed
                        if ($file->isDirectory()) { ?>
                        	<a class="directory" href="?path=<?= $file ?>"> <?= $file->getName() ?> </a>
                        <? } else {
                        	echo $file->getName();
                        } ?>	
                        </td><td class="tdedit">
			<? //Edit
                        if ($file->isFile()) { ?>
				<a href="?action=edit&path=<?= $file ?>"><? echo ($file->canWrite()) ? "Edit" : "Read"; ?> </a>
			<? } ?>
			</td><td class="tddownload">
                        <? //Download
                        if ($file->isFile()) { ?>
                        	<a href="?path=<?= $file ?>&action=download">Download</a>
                        <? } ?>
                        </td><td class="tddelete">
			<? //Delete
                        if ($file->canWrite()) { ?>
				<a href='?action=delete&path=<?= $file ?>'>Delete</a>
			<? } ?>
			</td><td class="tdlink">
			<? //Link
			if (strpos($file, $this->config->WEBSERVER_ROOT) === 0) { ?>
				<a href="<?= substr($file, strlen($this->config->WEBSERVER_ROOT)) ?>">Web Link</a>
			<? } ?>
			</td>
		<? } 
		if ($this->path->canWrite()) {
		?>
		<tr>
			<td colspan="100%"><form action="?action=cfile&path=<?= $this->path->getDirectory() ?>" method=POST>
				<input name=filename />
				<input type=submit value="Create File" />
			</form></td>
		</tr>
		<tr>
			<td colspan="100%"><form action="?action=cdir&path=<?= $this->path->getDirectory() ?>" method=POST>
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
	}

	function buildEdit() { 
		$file = new JFile($_REQUEST['path']);
		if (!$file->exists() || !$file->isFile()) {$this->buildBrowse();return;}

		if ($this->config->CODEMIRROR_ENABLED) {
		?>
		<script type="text/javascript" src="http://code.jquery.com/jquery-1.7.2.min.js"></script>
		<script src="CodeMirror2/lib/codemirror.js"></script>
		<script src="CodeMirror2/mode/javascript/javascript.js"></script>
		<script src="CodeMirror2/mode/clike/clike.js"></script>
		<script src="CodeMirror2/mode/css/css.js"></script>
		<script src="CodeMirror2/mode/diff/diff.js"></script>
		<script src="CodeMirror2/mode/haskell/haskell.js"></script>
		<script src="CodeMirror2/mode/stex/stex.js"></script>
		<script src="CodeMirror2/mode/xml/xml.js"></script>
		<script src="CodeMirror2/mode/python/python.js"></script>
		<script src="CodeMirror2/mode/htmlmixed/htmlmixed.js"></script>
		<script src="CodeMirror2/mode/php/php.js"></script>
		<script type="text/javascript" src="edit.js"></script>

		<? } ?>

		<form action="?action=save&path=<?= $file ?>" method="POST">
			<? if ($file->canWrite()) { ?>
				<input type="submit" value="Save" />
			<? } else { ?>Read only<? } ?>
			<a href="?path=<?= $file->getParent(); ?>">Back to Directory</a>
			<div id="edit_filename"><?= $file ?></div>
			
			<div class="nojs" id="syntax_modes">
				<div mode="php" class="selected">PHP</div>
				<div mode="javascript">Javascript</div>
				<div mode="htmlmixed">HTML</div>
				<div mode="css">CSS</div>
				<div mode="xml">XML</div>
				<div mode="text/x-csrc">C</div>
				<div mode="python">Python</div>
			</div>
			<textarea id="code" class="nojs" name="browser_file_content"><?= $file->getContent() ?></textarea>
		</form>
	<? }

	function save() {
		if ($this->path_changed || !$this->path->isFile() || !isset($_REQUEST['browser_file_content'])) return;
		$this->path->setContent($_REQUEST['browser_file_content']);
	}
	
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
	<link rel="stylesheet" href="CodeMirror2/lib/codemirror.css">
	<link rel="stylesheet" href="phpbrowser.css">
        <title>Browser</title>
</head>
<body>
<a id="logout" href="?action=logout">Change User</a>
<?php
if ($phpbrowser->login()) {
	switch ($_REQUEST['action']) {
		case 'logout':
			$phpbrowser->logout();
			$phpbrowser->buildLogin();
			break;
		case 'save':
			$phpbrowser->save();
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
} else {
	$phpbrowser->buildLogin();
}
?>
</body>
</html>
