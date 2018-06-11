<?php  

class GoogleDrive {

	const REFRESH_TOKEN = "1/vYXJgQVx2ZQhXq1v52rC3mM7fVrBqWJHGyw0ErmVAjzIEbxZyRtC5MPCXW4XjA1a",
		REFRESH_URL = "https://www.googleapis.com/oauth2/v4/token",
		UPLOAD_URL = "https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart",
		CREDENTIALS_FILE = "/../../../.credentials/google.ini";

	private $_client_id, $_client_secret;

	public function __construct(){
		$credentials = parse_ini_file(dirname(__FILE__) . self::CREDENTIALS_FILE, true);
		$this->_client_id = $credentials["drive"]["client_id"];
		$this->_client_secret = $credentials["drive"]["client_secret"];
	}

	public function uploadMedia($media){
		$token = $this->getAccessToken();
		$response = $this->upload($media, $token->access_token);
		return $response;
	}

	private function getAccessToken(){
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_URL, self::REFRESH_URL);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
			"grant_type" => "refresh_token",
			"client_id" => $this->_client_id,
			"client_secret" => $this->_client_secret,
			"refresh_token" => self::REFRESH_TOKEN
		)));

		$response = curl_exec($ch);
		curl_close($ch);

		return json_decode($response);
	}

	private function upload($media, $token){
		$ch = curl_init();

		$prefix = "--";
		$boundary = hash('sha256', uniqid('', true));
		$endl = "\n";

		$body = $prefix . $boundary . $endl
			. "Content-Type: application/json; charset=UTF-8" . $endl . $endl
			. "{" . $endl 
			. "\t" . "\"name\": \"" . basename($media) . "\"" . $endl
			. "}" . $endl . $endl
			. $prefix . $boundary . $endl
			. "Content-Type: video/mp4;" . $endl . $endl
			. file_get_contents($media) . $endl
			. $prefix . $boundary . $prefix;


		curl_setopt($ch, CURLOPT_URL, self::UPLOAD_URL);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		    "Content-Type: multipart/related; boundary=" . $boundary,
		    "Content-Length: " . strlen($body),
	        "Authorization: Bearer " . $token,
	    ));
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

		$response = curl_exec($ch);
		curl_close($ch);

		return json_decode($response, true);
	}


}