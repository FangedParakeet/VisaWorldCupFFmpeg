<?php 

class Dispatcher extends Slave {

	private $_dbh;

	public function __construct($dbh){
		$this->_dbh = $dbh;
	}

	public function enqueueJob(){
		list($chromaVid, $name, $email) = $this->checkGet(array("chroma", "name", "email"));
		$this->checkEmpty(array($chromaVid, $name, $email));
		$this->checkEmail(array($email));
		$this->checkFilepathExists(array($chromaVid));

		$deleteAfter = isset($_GET["deleteAfter"]) ? 1:0;
		$noResize = isset($_GET["noResize"]) ? 1:0;


		// if(isset($_GET["video"])){
		// 	$video = $_GET["video"];
		// 	$index = intval($video);
		// 	if($index < 1 || $index > 3){
		// 		throw new Exception("Webcam video index must be in range of 1-3");
		// 	}

		// 	$video = dirname(__FILE__) . "/../videos/user/webcam_SFTS_" . $video . ".mp4";

		// 	if(file_exists($video)){
		// 		$newVideo = dirname(__FILE__) . "/../videos/user/webcam_USER_" . time() . ".mp4";
		// 		rename($video, $newVideo);			
		// 	} else {
		// 		$newVideo = false;
		// 	}
		// } else {
			$newVideo = null;
		// }


		if(file_exists($chromaVid)){
			$newChroma = dirname(__FILE__) . "/../videos/user/AR_Video_USER_" . time() . ".mp4";
			rename($chromaVid, $newChroma);			
		} else {
			$newChroma = false;
		}

		$id = $this->generateId($name);

		$this->createJob($id, $newVideo, $newChroma, $name, $email, $deleteAfter, $noResize);

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
		$get = $this->_dbh->prepare("SELECT `jobId`, `status`, `statusCode`, `webcamVideo`, `arVideo`, `combinedVideo`, 
			`finalVideo`, `finalLink`, `name`, `email`, `noResize`, `toBeDeleted` FROM `jobs` WHERE `statusCode` > 0 
			ORDER BY `statusCode` DESC, `dateModified` ASC");
		$get->execute();
		$jobs = $get->fetchAll();

		return $jobs;
	}

	private function createJob($id, $video, $chromaVid, $name, $email, $deleteAfter, $noResize){
		$now = time();

		$status = "Ready";
		$statusCode = 1;

		if($video === false){
			$status = "Webcam video not present";
			$statusCode = -1;
			$video = null;
		}

		if($chromaVid == false){
			$status = "Chroma video not present";
			$statusCode = -1;
			$chromaVid = null;
		}

		$create = $this->_dbh->prepare("INSERT INTO `jobs` (`jobId`, `status`, `statusCode`, `webcamVideo`, 
			`arVideo`, `name`, `email`, `noResize`, `toBeDeleted`, `dateAdded`, `dateModified`) 
			VALUES (:jobId, :status, :statusCode, :webcamVideo, :arVideo, :name, :email, :noResize, :deleteAfter, :dateAdded, :dateModified)");
		$create->execute(array(
			"jobId" 		=> $id,
			"status" 		=> $status,
			"statusCode" 	=> $statusCode,
			"webcamVideo" 	=> $video,
			"arVideo" 		=> $chromaVid,
			"name" 			=> $name,
			"email" 		=> $email,
			"noResize" 		=> $noResize,
			"deleteAfter" 	=> $deleteAfter,
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
