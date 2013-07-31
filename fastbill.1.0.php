<?php

/* ********************************************	*/
/*	Copyright: DIGITALSCHMIEDE		*/
/*	http://www.digitalschmiede.de		*/
/* ********************************************	*/

class fastbill
{
	private $email = '';
	private $apiKey = '';
	private $apiUrl = 'https://my.fastbill.com/api/1.0/api.php';
	
	public function __construct($email, $apiKey)
	{
		if($email != '' && $apiKey != '')
		{
			$this->email = $email;
			$this->apiKey = $apiKey;
			return true;
		}
		else
		{
			return false;	
		}
	}
	
	public function request($data)
	{
		if($data)
		{
			if($this->email != '' && $this->apiKey != '' && $this->apiUrl != '')
			{
				$data_string = json_encode($data);
				
				$JSON = file_get_contents($this->apiUrl, null, stream_context_create(array(
				'http' => array(
				'method' => 'POST',
				'header' => 'Content-Type: application/json; charset=utf-8' . "\r\n"
				. 'Content-Length: ' . strlen($data_string) . "\r\n"
				. 'Authorization: Basic ' . base64_encode($this->email.':'.$this->apiKey),
				'content' => $data_string,
				),
				)));
				
				$array = json_decode($JSON);
				return $array;
			}
			else
			{
				return false;	
			}
		}
		else
		{
			return false;	
		}
	}
}

?>
