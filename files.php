<?php

	function oldest($a, $b){
		$atime = strtotime($a["date"]);
		$btime = strtotime($b["date"]);

		if($atime == $btime){
			return 0;
		} else {
			return ($atime < $btime) ? -1:1;
		}

	}

	$files = array();
	if ($handle = opendir('videos/user/')) {
	    while (false !== ($file = readdir($handle))) {
	        if ($file != "." && $file != "..") {
	           $files[] = array(
	        		"name" => $file,
	        		"date" => date("Y-m-d H:i:s", filemtime(dirname(__FILE__) ."/videos/local/". $file))
	           );
	        }
	    }
	    closedir($handle);

	    usort($files, "oldest");
	}

	$json = json_encode($files);

	file_put_contents("files.json", $json);
	echo $json;