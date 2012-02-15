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
	public $USER_PASSWORD = 'roflroflnoobnoob';
	
	public $USER_PRISON = '.'; // This is the top directory for the user.

	public $USER_UPLOAD = false; // Is the user allowed to upload a file?
	public $USER_CREATE_DIR = true; // Is the user allowed to create a new directory?
	public $USER_DELETE = false; // Is the user allowed to delete a file or a directory?
	public $USER_EDIT = true; // Is the user allowed to edit a file?
	public $USER_BACKUP = false; // Is the user allowed to create a backup of a directoy?
	public $USER_RENAME = false; // Is the user allowed to rename a file/directory?
	
	// Superuser
	public $SUPERUSER_PASSWORD = '';

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

	function __construct() {
		$this->config = new Config();
	}

	function login($password=null) {
		if (isset($password)) {
			Util->setCookie($this->config->COOKIE_NAME, md5($password), 3);
		}
		if (isset($_COOKIE[$this->config->COOKIE_NAME]))
	}

	function action($action) {}

	/*
	* Echos html for the whole Webpage
	*/
	function build() {
		echo "Hello World";
	} 
}

// Class for general tasks
class Util {
	public static setCookie($key, $value, $expire_days) {
		setcookie($key, $value, time()+(86400*$expire_days));
	}
	public static deleteCookie($key) {
		setcookie($key);
	}
}
// Provides the html templates.
class Resouces {
}

$phpbrowser = new PhpBrowser();
$phpbrowser->build();
