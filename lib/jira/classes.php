<?php

use rdx\jira\Client;
use rdx\jira\Response;
use rdx\jira\Request;
use rdx\jira\User;
use rdx\jira\NoCache;

class MySuperResponse extends Response {

	public function __construct( Request $request, array $response ) {
		parent::__construct($request, $response);
	}

}

class MySuperUser extends User {

	public function __construct( Client $client, Response $response ) {
		$this->stuff = 'be private';
		parent::__construct($client, $response);
	}

}
