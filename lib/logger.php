<?php

class Logger {

	private $_log_file;

	public function __construct($logfile){
		$this->_log_file = fopen($logfile, "a");
	}

	public function message($id, $message){
		$line = date("Y-m-d H:i:s") . " -- Job ID: " . $id . " -- " . $message . "\n";
		fwrite($this->_log_file, $line);
		return;
	}

	public function error($id, $message){
		$line = date("Y-m-d H:i:s") . " -- Job ID: " . $id . " -- ERROR: " . $message . "\n";
		fwrite($this->_log_file, $line);
	}

	public function close(){
		fclose($this->_log_file);
	}

}