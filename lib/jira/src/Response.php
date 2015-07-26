<?php

namespace rdx\jira;

use rdx\jira\Request;

class Response {

	// Raw
	public $request;
	public $code = 0;
	public $status = '';
	public $headers = array();
	public $body = '';

	// Parsed
	public $response = array();
	public $info = array();

	public function __construct( Request $request, array $response ) {
		$this->request = $request;

		foreach ($response as $key => $value) {
			$this->$key = $value;
		}

		// Forget response source array, it's all in this object now
		$request->transport->response = null;
	}

}
