<?php  

class Mailgun {

	const SUBJECT = "Your very own video from Zlatan!";

	private $_api_key, $_domain;

	public function __construct($key, $domain){
		$this->_api_key = $key;
		$this->_domain = $domain;
	}

	public function send($email, $link){
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $this->_api_key);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$html = '<a href="' . $link . '" target="_blank">Click here for your video!</a>';
		$plain = strip_tags($this->br2nl($html));

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v2/'. $this->_domain .'/messages');
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('from' => 'support@'. $this->_domain,
		      'to' => $email,
		      'subject' => self::SUBJECT,
		      'html' => $html,
		      'text' => $plain));

		$j = json_decode(curl_exec($ch));

		$info = curl_getinfo($ch);

		if($info['http_code'] != 200)
			throw new Exception($j->message);

		curl_close($ch);

	}

	private function br2nl($string) {
	    return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
	}


}