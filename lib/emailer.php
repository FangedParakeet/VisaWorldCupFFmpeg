<?php  

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Emailer {

	const SMTP = "smtp.gmail.com",
		SUBJECT = "Shooting for the Stars Video",
		FROM = "Zlatan",
		CREDENTIALS_FILE = "/../../../.credentials/google.ini";

	private $_username, $_password, $_client;

	public function __construct($client){
		$credentials = parse_ini_file(__DIR__ . self::CREDENTIALS_FILE, true);
		
		$this->_username = $credentials["mail"]["username"];
		$this->_password = $credentials["mail"]["password"];

		$this->_client = $client;
	}

	public function send($name, $email, $link){
	    $this->_client->IsSMTP(); // enable SMTP
	    $this->_client->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
	    $this->_client->SMTPAuth = true; // authentication enabled
	    $this->_client->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
	    $this->_client->Host = self::SMTP;
	    $this->_client->Port = 465; // or 587
	    $this->_client->IsHTML(true);
	    $this->_client->Username = $this->_username;                 // SMTP username
	    $this->_client->Password = $this->_password;                           // SMTP password
	    $this->_client->SetFrom($this->_username, self::FROM);
	    $this->_client->Subject = self::SUBJECT;
	    $this->_client->Body    = '<a href="' . $link . '" target="_blank">CLICK TO DOWNLOAD VIDEO!</a>';
	    $this->_client->addAddress($email, $name);     // Add a recipient

	    $client->send();
	}
}