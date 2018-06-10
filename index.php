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


		// RUNNING COMPOSITING/EMAILING TASK

		// Mac Environment
		// $command = "/Applications/MAMP/bin/php/php7.2.1/bin/php " . dirname(__FILE__) . "/task.php";
		// $output = dirname(__FILE__) . "/test.log";
		// exec($command . " > " . $output . " &");

		// Windows Environment
		$command = "C:\\xampp\\php\\php " . dirname(__FILE__) . "/task.php > " . dirname(__FILE__) . "/task.log 2>nul";
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