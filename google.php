<?php

	require_once(dirname(__FILE__) . "/lib/googleDrive.php");

	$drive = new GoogleDrive();
	$result = $drive->uploadMedia(dirname(__FILE__) . "/videos/local/VisaEnd.mp4");

	echo "<pre>";
	var_dump($result);
