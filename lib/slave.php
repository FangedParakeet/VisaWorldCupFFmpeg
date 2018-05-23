<?php 

class Slave {
	protected function checkPost($params){
		return $this->checkRequest($params, $_POST);
	}
	protected function checkGet($params){
		return $this->checkRequest($params, $_GET);
	}
	protected function checkFiles($params){
		return $this->checkRequest($params, $_FILES);
	}
	protected function checkRequest($params, $request){
		$values = array();
		foreach ($params as $value) {
			if(isset($request[$value])){
				$values[] = $request[$value];
			} else {
				throw new Exception("Missing parameter: " . $value, 0);
			}
		}
		return $values;
	}
	protected function checkEmpty($values){
		foreach ($values as $key => $value) {
			if(trim($value) === ""){
				throw new Exception("Field cannot be empty", 0);
			}
		}
		return;
	}
	protected function checkEmail($values){
		foreach ($values as $value) {
			if(!filter_var($value, FILTER_VALIDATE_EMAIL)){
				throw new Exception("Email must be formatted correctly", 0);
			}
		}
		return;
	}
	protected function checkNumeric($values){
		foreach ($values as $value) {
			if(!is_numeric($value)){
				throw new Exception("Field must be numeric", 0);
			}
		}
		return;		
	}
	protected function checkLength($values){
		foreach ($values as $value) {
			if(strlen(rtrim($value["value"])) != $value["length"]){
				throw new Exception("Field has incorrect length", 0);
			}
		}
		return;		
	}
	protected function checkFileErrors($values){
		foreach ($values as $value) {
			if (!isset($value['error']) || is_array($value['error'])){
			    throw new Exception('Invalid file parameters');
			}
			switch ($value['error']) {
			    case UPLOAD_ERR_OK:
			        break;
			    case UPLOAD_ERR_NO_FILE:
			        throw new Exception('No file sent');
			    case UPLOAD_ERR_INI_SIZE:
			    case UPLOAD_ERR_FORM_SIZE:
			        throw new Exception('Exceeded filesize limit');
			    default:
			        throw new Exception('Unknown errors');
			}
		}
		return;
	}
	protected function checkFilesize($values, $max, $min = 0){
		foreach ($values as $value) {
			if ($value["size"] > $max){
			    throw new Exception('Exceeded filesize limit');
			}
			if($value["size"] < $min){
				throw new Exception("Filesize too small");
			}
		}
		return;
	}
	protected function checkFileType($values, $mimes){
		foreach ($values as $value) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			$ext = array_search($finfo->file($value['tmp_name']), $mimes, true);
			if ($ext === false) {
			    throw new Exception('Invalid file format');
			}
		}
		return;
	}

	protected function checkFilepathExists($values){
		foreach ($values as $key => $value) {
			if(!file_exists($value)){
				throw new Exception("Could not find file at: " . $value, 0);
			}
		}
		return;
	}
}
