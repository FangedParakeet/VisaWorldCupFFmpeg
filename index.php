<?php

	require_once("lib/config.php");
	require_once("lib/slave.php");
	require_once("lib/ffmpeg.php");

	$status = true;
	$message = "Saul Goodman";

	$ffmpegPath = $config["ffmpegPath"];
	$escapeChar = $config["escapeChar"];
	$ffmpeg = new Ffmpeg($ffmpegPath, $escapeChar);

	try {
		// Add name, email
		list($video, $chroma) = $ffmpeg->getFields(array("video", "chroma"));

		$mergedVideo = $ffmpeg->chromakeyVideoMerge($video, $chroma);
		$finalVideo = $ffmpeg->addVideoBookend($mergedVideo);

		unlink($mergedVideo);
	} catch(Exception $e){
		$status = false;
		$message = $e->getMessage();
	}

	header("Content-type: application/json");
	echo json_encode(array(
		"status" => $status,
		"message" => $message
	));