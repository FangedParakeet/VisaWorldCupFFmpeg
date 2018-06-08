<?php

class Ffmpeg extends Slave {

	const CHROMAKEY = "00FF00",
		VISA_LOGO_START = "videos/local/VisaStart.ts",
		VISA_LOGO_END = "videos/local/VisaEnd.ts";

	private $_ffmpeg_path, $_escape_char, $_webcam, $_audio, $_framerate, $_pixel_codec;

	public function __construct($path, $esc, $webcam = null, $audio = null, $framerate = null, $pixel_codec = null){
		$this->_ffmpeg_path = $path;
		$this->_escape_char = $esc;
		$this->_webcam = $webcam;
		$this->_audio = $audio;
		$this->_framerate = $framerate;
		$this->_pixel_codec = $pixel_codec;
	}

	public function getFields($fields){
		list($video, $chroma, $name, $email) = $this->checkGet($fields);
		$this->checkEmpty(array($video, $chroma, $name, $email));
		$this->checkEmail(array($email));
		$name = preg_replace('/\s+/', '', $name);
		
		return array($video, $chroma, $name, $email);
	}

	public function recordWebcam(){
		list($index) = $this->checkget(array("index"));

		$output = dirname(__FILE__) . "/../videos/user/webcam_SFTS_" . $index .".mp4";
		if(file_exists($output)){
			unlink($output);
		}

		$command = $this->_ffmpeg_path . " -f dshow -video_size 1280x720 -framerate " . $this->_framerate . " " . $this->_pixel_codec . " -i video=\"" . $this->_webcam ."\" -y -t 00:00:10 " . $output . " > " . dirname(__FILE__) . "/ffmpeg.log 2>nul";

		// $result = exec($command, $error, $status);
		$exec = popen("start /B " . $command, "r");
		pclose($exec);

		return $output;
	}

	public function chromakeyVideoMerge($video, $chromaVid, $jobId, $noResize){
		if(!file_exists($video)){
			throw new Exception("Could not find webcam video: " . $video);
		}
		if(!file_exists($chromaVid)){
			throw new Exception("Could not find AR video: " . $chromaVid);
		}

		if(!$noResize){
			$scaledOut = dirname(__FILE__) . "/../videos/user/" . $job["jobId"] . "-scaledAr.mp4";
			$scale = $this->_ffmpeg_path . " -i " . $chromaVid . " -vf scale=1280:720 " . $scaledOut;			

			$result = exec($scale, $error, $status);
		} else {
			$scaledOut = $chromaVid;
		}

		$outVideo = dirname(__FILE__) . "/../videos/user/" . $jobId . "-alpha.mp4";
		$command = $this->_ffmpeg_path . " -i " . $video . " -i " . $scaledOut ." -filter_complex " . $this->_escape_char ."[1:v]colorkey=0x" . self::CHROMAKEY . ":0.3:0.2[ckout];[0:v][ckout]overlay[out]" . $this->_escape_char . " -map " . $this->_escape_char . "[out]" . $this->_escape_char ." " . $outVideo;

		// Add logging
		$result = exec($command, $error, $status);

		unlink($video);
		unlink($chromaVid);
		unlink($scaledOut);

		return $outVideo;
	}

	public function addVideoBookend($video, $id = "SODEAU"){
		if(!file_exists($video)){
			throw new Exception("Could not find source video: " . $video);
		}

		$outputFilename = dirname(__FILE__) . "/../videos/user/" . $id . ".mp4";
		$visaLogo1 = dirname(__FILE__) . "/../" . self::VISA_LOGO_START;
		$visaLogo2 = dirname(__FILE__) . "/../" . self::VISA_LOGO_END;
		$tempOut = dirname(__FILE__) . "/../videos/user/" . $id . ".ts";

		$temp = $this->_ffmpeg_path . " -i " . $video . " -c copy -bsf:v h264_mp4toannexb -f mpegts " . $tempOut;

		$command = $this->_ffmpeg_path . " -i \"concat:" . $visaLogo1 ."|" . $tempOut ."|" . $visaLogo2 ."\" -bsf:a aac_adtstoasc " . $outputFilename;

		// Add logging
		$result = exec($temp, $error, $status);
		$result = exec($command, $error, $status);

		unlink($tempOut);

		return $outputFilename;
	}


}