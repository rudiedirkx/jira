<?php

namespace rdx\jira;

use rdx\jira\Request;

class BasicAuth implements Auth {

	public $user = '';
	public $auth = '';

	/**
	 *
	 */
	public function __construct( $user, $pass ) {
		$this->user = $user;
		$this->auth = base64_encode($user . ':' . $pass);
	}

	/**
	 *
	 */
	public function signRequest( Request $request ) {
		$request->build();

		// Add HTTP Basic Auth header
		$request->transport->setHeader('Authorization', 'Basic ' . $this->auth);
	}

}
