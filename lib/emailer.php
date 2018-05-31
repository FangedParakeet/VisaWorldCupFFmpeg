<?php  

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Emailer {

	const SMTP = "smtp.gmail.com",
		SUBJECT = "Shooting for the Stars Video",
		FROM = "Zlatan",
		CREDENTIALS_FILE = "/../../../.credentials/google.ini";

	private $_username, $_password, $_client;

	public function __construct(){
		$credentials = parse_ini_file(__DIR__ . self::CREDENTIALS_FILE, true);

		$this->_username = $credentials["mail"]["username"];
		$this->_password = $credentials["mail"]["password"];
	}

	public function send($name, $email, $link){
			$mail = new PHPMailer(true); // Passing `true` enables exceptions

			ob_start();
			include(__DIR__ . "/template.php");
			$body = ob_get_contents();
			ob_end_clean();

		    $mail->IsSMTP(); // enable SMTP
		    $mail->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
		    $mail->SMTPAuth = true; // authentication enabled
		    $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
		    $mail->Host = self::SMTP;
		    $mail->Port = 465; // or 587
		    $mail->IsHTML(true);
		    $mail->Username = $this->_username;                 // SMTP username
		    $mail->Password = $this->_password;                        // SMTP password
		    $mail->SetFrom($this->_username, self::FROM);
		    $mail->Subject = self::SUBJECT;
		    $mail->Body    = $body;
		    $mail->addAddress($email, $name);     // Add a recipient

		    $mail->send();
		    return;
	}

}