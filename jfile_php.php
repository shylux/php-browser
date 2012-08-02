<?php
// File class to handle files and directorys
// Java File class acted as model
class JFile {
	// the absolute path including filename and extension
	public $path;

	public function __construct($path, $filename = "") {
		if (strlen($filename) > 0) $filename = DIRECTORY_SEPARATOR . $filename;
		$this->path = realpath((string)$path . (string)$filename);
		if (!is_string($this->path)) trigger_error("Error in JFile create: ".(string)$path . (string)$filename);
	}
	public static function virtual($path, $filename = "") {
		if (strlen($filename) > 0) $filename = DIRECTORY_SEPARATOR . $filename;
		$vfile = new JFile('/');
		$vfile->path = (String)$path . $filename;
		return $vfile;
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
	public function canExecute() {
		return is_executable($this->path);
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
	public function setContent($cont) {
		return file_put_contents($this->path, $cont);
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
	public function renameTo($newpath) {
		return rename($this->path, $newpath);
	}
	public function delete() {
		if ($this->isFile()) return unlink($this);
		if ($this->isDirectory()) {
			foreach ($this->listFiles() as $file) {
				$file->delete();
			}
			rmdir($this);
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
		$files = scandir($dir); 
		$filearray = array();
		foreach ($files as $file) {
			if ($file == "." || $file == "..") continue;
			array_push($filearray, new JFile($dir, $file));
		}
		return $filearray;
	}	
}

if (isset($argv) && in_array("test", $argv)) {
	p("Start testing...");
	p("Setting up direcetory '.'");
	$f = new JFile('.');
	p(".toString()");
	echo $f;
	p("read, write and execute");
	var_dump($f->canRead());
	var_dump($f->canWrite());
	var_dump($f->canExecute());
	p("exists");
	var_dump($f->exists());
	p("isDir, isFile");
	var_dump($f->isDirectory());
	var_dump($f->isFile());
	p("getName");
	echo $f->getName();
	p("last modified");
	var_dump($f->lastModified());
	p("Setting up test file...");
	$f->createNewFile('php_test_file');
	$t = new JFile('./php_test_file');
	p("setContent");
	$t->setContent("test");
	p("getContent, length");
	echo $t->getContent();
	var_dump($t->length());
	p("renameTo");
	$t->renameTo('php_test_file2');
	$t = new JFile('./php_test_file2');
	p("getDirectory");
	echo $t->getDirectory();
	p("getParent");
	echo $t->getParent();
	p("listFiles");
	var_dump($f->listFiles());
	p("delete");
	$t->delete();
}

function p($testmsg) {
	echo "\n===  $testmsg\n";
}
?>
