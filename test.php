<?php

	$ffmpegPath = "/usr/local/bin/ffmpeg";


	// Green screen video
	$green = "greenzlat.mp4";
	$video = "rcjod.mp4";
	$output = "newresult.mp4";

	$command = $ffmpegPath . " -i " . $video . " -i " . $green ." -filter_complex '[1:v]colorkey=0x00FF00:0.3:0.2[ckout];[0:v][ckout]overlay[out]' -map '[out]' " . $output;

	$result = exec($command, $error, $status);

	// Concatenate video
	$visa = "visa.mp4";

	$cmd1 = $ffmpegPath . " -i " . $visa . " -c copy -bsf:v h264_mp4toannexb -f mpegts intermediate1.ts";
	$cmd2 = $ffmpegPath . " -i " . $output . " -c copy -bsf:v h264_mp4toannexb -f mpegts intermediate2.ts";
	$cmd3 = $ffmpegPath . " -i \"concat:intermediate1.ts|intermediate2.ts|intermediate1.ts\" -bsf:a aac_adtstoasc output.mp4";

	$result = exec($cmd1);
	$result = exec($cmd2);
	$result = exec($cmd3);

	echo $cmd3;