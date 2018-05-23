<?php 

	require_once(dirname(__FILE__) . "/lib/config.php");
	require_once(dirname(__FILE__) . "/lib/db.php");
	require_once(dirname(__FILE__) . "/lib/aws/aws-autoloader.php");
	require_once(dirname(__FILE__) . "/lib/slave.php");
	require_once(dirname(__FILE__) . "/lib/ffmpeg.php");
	require_once(dirname(__FILE__) . "/lib/dispatcher.php");
	require_once(dirname(__FILE__) . "/lib/logger.php");

	date_default_timezone_set($config["timezone"]);

	use Aws\S3\S3Client;
	use Aws\S3\Exception\S3Exception;

	$awsBucket = $config["awsBucket"];
	$awsRegion = $config["awsRegion"];
	                        
	$s3 = new S3Client([
	    'version' => 'latest',
	    'region'  => $awsRegion,
	    'profile' => 'default'
	]);

	$logger = new Logger($config["logfile"]);

	$ffmpegPath = $config["ffmpegPath"];
	$escapeChar = $config["escapeChar"];
	$ffmpeg = new Ffmpeg($ffmpegPath, $escapeChar);

	$dispatcher = new Dispatcher($dbh);
	$jobs = $dispatcher->getJobs();

	foreach ($jobs as $job) {
		try {

			switch(intval($job["statusCode"])){

				// Videos ready for merge
				case 1:
					if(is_null($job["combinedVideo"])){
						$logger->message($job["jobId"], "Combining webcam and AR video...");

						$webcamVideo = dirname(__FILE__) . "/" . $job["webcamVideo"];
						$arVideo = dirname(__FILE__) . "/" . $job["arVideo"];
						$outVideo = dirname(__FILE__) . "/videos/user/" . $job["jobId"] . "-alpha";

						$mergedVideo = $ffmpeg->chromakeyVideoMerge($webcamVideo, $arVideo, $outVideo);
						if(!file_exists($mergedVideo)){
							$error = "Failed to combine webcam and AR video!";
							$logger->error($job["jobId"], $error);

							$dispatcher->updateJob($job["jobId"], array(
								"statusCode" => -1,
								"status" => $error
							));
							break;
						}
						$dispatcher->updateJob($job["jobId"], array(
							"combinedVideo" => $mergedVideo
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
							"status" => $error
						));
						break;						
					}

					$logger->message($job["jobId"], "Final video complete!");

					unlink($mergedVideo);

					$dispatcher->updateJob($job["jobId"], array(
						"statusCode" => 2,
						"status" => "Video Ready",
						"finalVideo" => $finalVideo
					));
					break;

				// Video merge complete, upload to S3, send email
				case 2:
					if(is_null($job["finalLink"])){
						$logger->message($job["jobId"], "Uploading final video to S3...");

						$result = $s3->putObject(array(
						    'Bucket'     => $awsBucket,
						    'Key'        => basename($job["finalVideo"]),
						    'SourceFile' => dirname(__FILE__) . "/" . $job["finalVideo"],
						));

						if(isset($result["ObjectURL"])){

							$logger->message($job["jobId"], "Video uploaded successfully!");

							$finalLink = $result["ObjectURL"];

							$dispatcher->updateJob($job["jobId"], array(
								"finalLink" => $finalLink
							));	

						} else {

							$logger->error($job["jobId"], "Video upload failed!");

						}
					} else {

						$finalLink = $job["finalLink"];

					}
					break;

			}
			
		} catch(Exception $e){
			$logger->error($job["jobId"], $e->getMessage());
			$dispatcher->updateJob($job["jobId"], array(
				"statusCode" => -1,
				"status" => $e->getMessage()
			));
		}
	}

	$logger->close();

