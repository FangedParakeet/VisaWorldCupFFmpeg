<?php

	$host = $config["host"];
	$db = $config["db"];
	$port = $config["port"];
	$user = $config["user"];
	$password = $config["pass"];
	
	$dsn = "mysql:host=$host;dbname=$db;port=$port";
	 
	try{
	 	// create a PDO connection with the configuration data
	 	$dbh = new PDO($dsn, $user, $password);
	 
	}catch (PDOException $e){
	 	// report error message
	 	echo $e->getMessage();
	}

