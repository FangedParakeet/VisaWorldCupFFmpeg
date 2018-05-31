<?php 

class Dispatcher extends Slave {

	private $_dbh;

	public function __construct($dbh){
		$this->_dbh = $dbh;
	}

	public function enqueueJob(){
		list($video, $chromaVid, $name, $email) = $this->checkGet(array("video", "chroma", "name", "email"));
		$this->checkEmpty(array($video, $chromaVid, $name, $email));
		$this->checkEmail(array($email));
		$this->checkFilepathExists(array($chromaVid));

		$index = intval($video);
		if($index < 1 || $index > 3){
			throw new Exception("Webcam video index must be in range of 1-3");
		}

		$id = $this->generateId($name);

		$this->createJob($id, $video, $chromaVid, $name, $email);

		return $id;
	}

	public function checkJob(){
		list($id) = $this->checkGet(array("id"));
		$this->checkEmpty(array($id));

		$job = $this->getJob($id);

		if(!isset($job["jobId"])){
			throw new Exception("Could not find job with ID: " . $id);
		}

		return $job;
	}

	public function updateJob($id, $params){
		$values = array();
		$args = array();

		foreach ($params as $key => $value) {
			$args[] = "`" . $key . "` = ?";
			$values[] = $value;
		}
		$values[] = $id;

		$sql = "UPDATE `jobs` SET " . implode(", ", $args) . " WHERE `jobId` = ? LIMIT 1";
		$set = $this->_dbh->prepare($sql);
		$set->execute($values);

		return;
	}

	public function getJobs(){
		$get = $this->_dbh->prepare("SELECT `jobId`, `status`, `statusCode`, `webcamVideo`, `arVideo`, `finalVideo`, 
			`finalLink`, `name`, `email` FROM `jobs` WHERE `statusCode` > 0 
			ORDER BY `statusCode` DESC, `dateModified` ASC");
		$get->execute();
		$jobs = $get->fetchAll();

		return $jobs;
	}

	private function createJob($id, $video, $chromaVid, $name, $email){
		$now = time();

		$create = $this->_dbh->prepare("INSERT INTO `jobs` (`jobId`, `status`, `statusCode`, `webcamVideo`, 
			`arVideo`, `name`, `email`, `dateAdded`, `dateModified`) 
			VALUES (:jobId, :status, :statusCode, :webcamVideo, :arVideo, :name, :email, :dateAdded, :dateModified)");
		$create->execute(array(
			"jobId" 		=> $id,
			"status" 		=> "Ready",
			"statusCode" 	=> 1,
			"webcamVideo" 	=> $video,
			"arVideo" 		=> $chromaVid,
			"name" 			=> $name,
			"email" 		=> $email,
			"dateAdded" 	=> date("Y-m-d H:i:s", $now),
			"dateModified" 	=> date("Y-m-d H:i:s", $now)
		));

		return;
	}

	private function getJob($id){
		$get = $this->_dbh->prepare("SELECT `jobId`, `status`, `statusCode`, `finalVideo`, `finalLink`, `dateAdded`, `dateModified` 
			FROM `jobs` WHERE `jobId` = :jobId LIMIT 1");
		$get->execute(array("jobId" => $id));

		$job = $get->fetch();

		return $job;
	}

	private function generateId($name){
		do {
			$id = md5(preg_replace('/\s+/', '', $name) . time());

			$get = $this->_dbh->prepare("SELECT `jobId` FROM `jobs` WHERE `jobId` = :jobId");
			$get->execute(array("jobId" => $id));

			$results = $get->fetchAll();
		} while(count($results) > 0);

		return $id;
	}

}
