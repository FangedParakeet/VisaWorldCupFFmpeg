<?php

	require_once("lib/config.php");
	require_once("lib/db.php");
	require_once("lib/slave.php");
	require_once("lib/dispatcher.php");

	date_default_timezone_set($config["timezone"]);

	$status = true;
	$message = "Saul Goodman";
	$jobId = "";

	$dispatcher = new Dispatcher($dbh);

	try {

		$jobId = $dispatcher->enqueueJob();

		$command = "php " .dirname(__FILE__) . "/task.php > " . dirname(__FILE__) . "/lib/ffmpeg.log 2>nul";
		$exec = popen("start /B " . $command, "r");
		pclose($exec);

	} catch(Exception $e){
		$status = false;
		$message = $e->getMessage();
	}

	header("Content-type: application/json");
	echo json_encode(array(
		"status" => $status,
		"message" => $message,
		"jobId" => $jobId
	));