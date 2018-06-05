<?php

	require_once("lib/config.php");
	require_once("lib/slave.php");
	require_once("lib/ffmpeg.php");

	date_default_timezone_set($config["timezone"]);

	$status = true;
	$message = "Saul Goodman";
	$video = "";

	$ffmpegPath = $config["ffmpegPath"];
	$escapeChar = $config["escapeChar"];
	$webcam = $config["webcam"];
	$audio = $config["audio"];
	$framerate = $config["framerate"];
	$pixel = $config["pixel"];
	$ffmpeg = new Ffmpeg($ffmpegPath, $escapeChar, $webcam, $audio, $framerate, $pixel);

	try {
		
		$video = $ffmpeg->recordWebcam();

	} catch(Exception $e){
		$status = false;
		$message = $e->getMessage();
	}

	header("Content-type: application/json");
	echo json_encode(array(
		"status" => $status,
		"message" => $message,
		"video" => $video
	));