<?php 

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	require_once(dirname(__FILE__) . "/lib/config.php");
	require_once(dirname(__FILE__) . "/lib/db.php");
	require_once(dirname(__FILE__) . "/lib/slave.php");
	require_once(dirname(__FILE__) . "/lib/ffmpeg.php");
	require_once(dirname(__FILE__) . "/lib/googleDrive.php");
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

				// Video merge complete, upload to Google, send email
				case 2:
					if(is_null($job["finalLink"])){
						$logger->message($job["jobId"], "Uploading final video to Google Drive...");

						$drive = new GoogleDrive();
						$result = $drive->uploadMedia($job["finalVideo"]);

						if(isset($result["id"])){

							$logger->message($job["jobId"], "Video uploaded successfully!");

							$finalLink = "https://drive.google.com/file/d/" . $result["id"] . "/edit?usp=sharing";

							$dispatcher->updateJob($job["jobId"], array(
								"finalLink" => $finalLink
							));	

						} else {

							$logger->error($job["jobId"], "Video upload failed!");
							break;

						}
					} else {

						$finalLink = $job["finalLink"];

					}

					$logger->message($job["jobId"], "Sending email...");

					$credentials = parse_ini_file(__DIR__ . "/../../.credentials/google.ini", true);
					$username = $credentials["mail"]["username"];
					$password = $credentials["mail"]["password"];

					$mail = new PHPMailer(true); // Passing `true` enables exceptions

				    $mail->IsSMTP(); // enable SMTP
				    $mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
				    $mail->SMTPAuth = true; // authentication enabled
				    $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
				    $mail->Host = "smtp.gmail.com";
				    $mail->Port = 465; // or 587
				    $mail->IsHTML(true);
				    $mail->Username = $username;                 // SMTP username
				    $mail->Password = $password;                        // SMTP password
				    $mail->SetFrom($username, "Zlatan");
				    $mail->Subject = 'Shooting for the Stars Video';
				    $mail->Body    = '<a href="' . $finalLink . '" target="_blank">CLICK TO DOWNLOAD VIDEO!</a>';
				    $mail->addAddress($job["email"], $job["name"]);     // Add a recipient

				    $mail->send();

				    $dispatcher->updateJob($job["jobId"], array(
				    	"statusCode" => 0,
				    	"status" => "Finished"
				    ));
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
