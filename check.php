<?php

	require_once("lib/config.php");
	require_once("lib/db.php");
	require_once("lib/slave.php");
	require_once("lib/dispatcher.php");

	date_default_timezone_set($config["timezone"]);

	$status = true;
	$message = "Saul Goodman";
	$statusMessage = "";
	$statusCode = -1;
	$video = "";
	$link = "";
	$dateAdded = "";
	$dateModified = "";

	$dispatcher = new Dispatcher($dbh);

	try {
		
		$job = $dispatcher->checkJob();

		$statusMessage = $job["status"];
		$statusCode = intval($job["statusCode"]);
		$video = is_null($job["finalVideo"]) ? "" : $job["finalVideo"];
		$link = is_null($job["finalLink"]) ? "" : $job["finalLink"];
		$dateAdded = $job["dateAdded"];
		$dateModified = $job["dateModified"];

	} catch(Exception $e){
		$status = false;
		$message = $e->getMessage();
	}

	header("Content-type: application/json");
	echo json_encode(array(
		"status" => $status,
		"message" => $message,
		"jobStatus" => $statusMessage,
		"jobStatusCode" => $statusCode,
		"video" => $video,
		"link" => $link,
		"dateAdded" => $dateAdded,
		"dateModified" => $dateModified
	));