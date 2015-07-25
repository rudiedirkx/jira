<?php

namespace rdx\jira;

use rdx\jira\JiraEntity;
use rdx\jira\Client;
use rdx\jira\Response;
use rdx\jira\exception\NotFoundException;
use rdx\jira\exception\AccessDeniedException;

// @see rdx\jira\Issue
// @see rdx\jira\User
abstract class JiraEntity {

	public $client;
	public $response;

	/**
	 *
	 */
	public function __construct( Client $client, Response $response ) {
		if ( $response->code == 404 ) {
			throw new NotFoundException;
		}
		else if ( $response->code == 403 ) {
			throw new AccessDeniedException;
		}

		$this->client = $client;
		$this->response = $response;

		// Copy Entity properties from Response to Entity
		foreach ( $response->response as $field => $value ) {
			$this->$field = $value;
		}
	}

}
