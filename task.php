<?php 

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	require_once(dirname(__FILE__) . "/lib/config.php");
	require_once(dirname(__FILE__) . "/lib/db.php");
	require_once(dirname(__FILE__) . "/lib/slave.php");
	require_once(dirname(__FILE__) . "/lib/ffmpeg.php");
	require_once(dirname(__FILE__) . "/lib/googleDrive.php");
	require_once(dirname(__FILE__) . "/lib/emailer.php");
	require_once(dirname(__FILE__) . "/lib/dispatcher.php");
	require_once(dirname(__FILE__) . "/lib/logger.php");
	require_once(dirname(__FILE__) . "/vendor/autoload.php");

	date_default_timezone_set($config["timezone"]);

	$logger = new Logger($config["logfile"]);

	$ffmpegPath = $config["ffmpegPath"];
	$escapeChar = $config["escapeChar"];
	$ffmpeg = new Ffmpeg($ffmpegPath, $escapeChar);

	$dispatcher = new Dispatcher($dbh);
	$jobs = $dispatcher->getJobs();

	$logger->message("N/A", "Starting task with " . count($jobs) . " jobs...");

	while(count($jobs) > 0){

		foreach ($jobs as $job) {
			try {

				switch(intval($job["statusCode"])){

					// Videos ready for merge
					case 1:
						if(is_null($job["combinedVideo"])){
							$logger->message($job["jobId"], "Combining webcam and AR video...");

							$webcamVideo = $job["webcamVideo"];
							$arVideo = $job["arVideo"];
							$noResize = intval($job["noResize"]) == 0;

							$mergedVideo = $ffmpeg->chromakeyVideoMerge($webcamVideo, $arVideo, $job["jobId"], $noResize);
							if(!file_exists($mergedVideo)){
								$error = "Failed to combine webcam and AR video!";
								$logger->error($job["jobId"], $error);

								$dispatcher->updateJob($job["jobId"], array(
									"statusCode" => -1,
									"status" => $error,
									"dateModified" => date("Y-m-d H:i:s")
								));
								break;
							}
							$dispatcher->updateJob($job["jobId"], array(
								"combinedVideo" => $mergedVideo,
								"dateModified" => date("Y-m-d H:i:s")
							));
						} else {
							$mergedVideo = $job["combinedVideo"];
						}

						$logger->message($job["jobId"], "Adding VISA logo to video...");

						$finalVideo = $ffmpeg->addVideoBookend($mergedVideo, $job["jobId"]);
						if(!file_exists($finalVideo)){
							$error = "Failed to add client logo to video!";
							$logger->error($job["jobId"], $error);

							$dispatcher->updateJob($job["jobId"], array(
								"statusCode" => -1,
								"status" => $error,
								"dateModified" => date("Y-m-d H:i:s")
							));
							break;						
						}

						$logger->message($job["jobId"], "Final video complete!");

						unlink($mergedVideo);

						$dispatcher->updateJob($job["jobId"], array(
							"statusCode" => 2,
							"status" => "Video Ready",
							"finalVideo" => $finalVideo,
							"dateModified" => date("Y-m-d H:i:s")
						));
						break;

					// Video merge complete, upload to Google, send email
					case 2:
						if(is_null($job["finalLink"])){
							$logger->message($job["jobId"], "Uploading final video to Google Drive...");

							$drive = new GoogleDrive();
							$result = $drive->uploadMedia($job["finalVideo"]);

							if(isset($result["id"])){

								$logger->message($job["jobId"], "Video uploaded successfully!");

								if(intval($job["toBeDeleted"])){
									unlink($job["finalVideo"]);
								}

								$finalLink = "https://drive.google.com/file/d/" . $result["id"] . "/edit?usp=sharing";

								$dispatcher->updateJob($job["jobId"], array(
									"finalLink" => $finalLink,
									"dateModified" => date("Y-m-d H:i:s")
								));	

							} else {

								$logger->error($job["jobId"], "Video upload failed!");
								break;

							}
						} else {

							$finalLink = $job["finalLink"];

						}

						$logger->message($job["jobId"], "Sending email...");

						$email = new Emailer();
						$email->send($job["name"], $job["email"], $finalLink);

						$logger->message($job["jobId"], "Email sent successfully!");

					    $dispatcher->updateJob($job["jobId"], array(
					    	"statusCode" => 0,
					    	"status" => "Finished",
					    	"dateModified" => date("Y-m-d H:i:s")
					    ));
						$logger->message($job["jobId"], "Job complete!");
						break;

				}

				
			} catch(\Exception $e){
				$logger->error($job["jobId"], $e->getMessage());
				$dispatcher->updateJob($job["jobId"], array(
					"statusCode" => -1,
					"status" => $e->getMessage()
				));
			}

		}

		$jobs = $dispatcher->getJobs();
	}

	$logger->message("N/A", "Task complete!");
	$logger->close();
