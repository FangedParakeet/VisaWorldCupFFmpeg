<?php

class Ffmpeg extends Slave {

	const CHROMAKEY = "00FF00",
		VISA_LOGO = "videos/local/visa.mp4";

	private $_ffmpeg_path, $_escape_char, $_webcam, $_audio;

	public function __construct($path, $esc, $webcam = null, $audio = null){
		$this->_ffmpeg_path = $path;
		$this->_escape_char = $esc;
		$this->_webcam = $webcam;
		$this->_audio = $audio;
	}

	public function getFields($fields){
		list($video, $chroma, $name, $email) = $this->checkGet($fields);
		$this->checkEmpty(array($video, $chroma, $name, $email));
		$this->checkEmail(array($email));
		$name = preg_replace('/\s+/', '', $name);
		
		return array($video, $chroma, $name, $email);
	}

	public function recordWebcam(){
		$output = dirname(__FILE__) . "/../videos/local/" . time() . "visa.mp4";
		while(file_exists($output)){
			$output = dirname(__FILE__) . "/../videos/local/" . time() . "visa.mp4";
		}

		$command = $this->_ffmpeg_path . " -f dshow -video_size 1280x720 -framerate 30 -pixel_format yuv420p -i video=\"" . $this->_webcam ."\":audio=\"" . $this->_audio ."\" -y -t 00:00:10 " . $output . " > " . dirname(__FILE__) . "/ffmpeg.log 2>nul";

		// $result = exec($command, $error, $status);
		$exec = popen("start /B " . $command, "r");

		return $output;
	}

	public function chromakeyVideoMerge($video, $chromaVid, $outputFilename = "videos/user/chResult"){
		if(!file_exists($video)){
			throw new Exception("Could not find webcam video: " . $video);
		}
		if(!file_exists($chromaVid)){
			throw new Exception("Could not find AR video: " . $chromaVid);
		}

		$outputFilename .= ".mp4";

		$command = $this->_ffmpeg_path . " -i " . $video . " -i " . $chromaVid ." -filter_complex " . $this->_escape_char ."[1:v]colorkey=0x" . self::CHROMAKEY . ":0.3:0.2[ckout];[0:v][ckout]overlay[out]" . $this->_escape_char . " -map " . $this->_escape_char . "[out]" . $this->_escape_char ." " . $outputFilename;

		// Add logging
		$result = exec($command, $error, $status);

		return $outputFilename;
	}

	public function addVideoBookend($video, $id = "SODEAU"){
		if(!file_exists($video)){
			throw new Exception("Could not find source video: " . $video);
		}

		$outputFilename = dirname(__FILE__) . "/../videos/user/" . $id . ".mp4";
		$visaLogo = dirname(__FILE__) . "/../" . self::VISA_LOGO;
		$tempOut1 = dirname(__FILE__) . "/../videos/user/" . $id . "1.ts";
		$tempOut2 = dirname(__FILE__) . "/../videos/user/" . $id . "2.ts";

		$temp1 = $this->_ffmpeg_path . " -i " . $visaLogo . " -c copy -bsf:v h264_mp4toannexb -f mpegts " . $tempOut1;
		$temp2 = $this->_ffmpeg_path . " -i " . $video . " -c copy -bsf:v h264_mp4toannexb -f mpegts " . $tempOut2;

		$command = $this->_ffmpeg_path . " -i \"concat:" . $tempOut1 ."|" . $tempOut2 ."|" . $tempOut2 ."\" -bsf:a aac_adtstoasc " . $outputFilename;

		// Add logging
		$result = exec($temp1, $error, $status);
		$result = exec($temp2, $error, $status);

		$result = exec($command, $error, $status);

		unlink($tempOut1);
		unlink($tempOut2);

		return $outputFilename;
	}


}