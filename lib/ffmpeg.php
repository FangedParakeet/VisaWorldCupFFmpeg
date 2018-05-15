<?php

class Ffmpeg extends Slave {

	const CHROMAKEY = "00FF00",
		VISA_LOGO = "videos/local/visa.mp4";

	private $_ffmpeg_path, $_escape_char;

	public function __construct($path, $esc){
		$this->_ffmpeg_path = $path;
		$this->_escape_char = $esc;
	}

	public function getFields($fields){
		list($video, $chroma, $name, $email) = $this->checkGet($fields);
		$this->checkEmpty(array($video, $chroma, $name, $email));
		$this->checkEmail(array($email));
		$name = preg_replace('/\s+/', '', $name);
		
		return array($video, $chroma, $name, $email);
	}

	public function chromakeyVideoMerge($video, $chromaVid, $outputFilename = "videos/user/chResult"){
		if(!file_exists($video)){
			throw new Exception("Could not find capture video: " . $video);
		}
		if(!file_exists($chromaVid)){
			throw new Exception("Could not find chromakey video: " . $chromaVid);
		}

		$outputFilename .= ".mp4";

		$command = $this->_ffmpeg_path . " -i " . $video . " -i " . $chromaVid ." -filter_complex " . $this->_escape_char ."[1:v]colorkey=0x" . self::CHROMAKEY . ":0.3:0.2[ckout];[0:v][ckout]overlay[out]" . $this->_escape_char . " -map " . $this->_escape_char . "[out]" . $this->_escape_char ." " . $outputFilename;

		// Add logging
		$result = exec($command, $error, $status);

		return $outputFilename;
	}

	public function addVideoBookend($video, $salt = "SODEAU"){
		if(!file_exists($video)){
			throw new Exception("Could not find source video: " . $video);
		}

		$id = $salt . time();
		$outputFilename = "videos/user/" . $id . ".mp4";

		$temp1 = $this->_ffmpeg_path . " -i " . self::VISA_LOGO . " -c copy -bsf:v h264_mp4toannexb -f mpegts videos/user/" . $id . "1.ts";
		$temp2 = $this->_ffmpeg_path . " -i " . $video . " -c copy -bsf:v h264_mp4toannexb -f mpegts videos/user/" . $id . "2.ts";

		$command = $this->_ffmpeg_path . " -i \"concat:videos/user/" . $id ."1.ts|videos/user/" . $id ."2.ts|videos/user/" . $id ."1.ts\" -bsf:a aac_adtstoasc " . $outputFilename;

		// Add logging
		$result = exec($temp1, $error, $status);
		$result = exec($temp2, $error, $status);

		$result = exec($command, $error, $status);

		$this->removeTempFiles($id);

		return $outputFilename;
	}

	private function removeTempFiles($id){
		unlink("videos/user/" . $id . "1.ts");
		unlink("videos/user/" . $id . "2.ts");
	}

}