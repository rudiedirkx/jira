<?php

namespace rdx\jira;

use rdx\jira\Client;

abstract class Request {

	public $apiPath = '';

	public $client;
	public $transport;

	public $method = '';
	public $path = '';
	public $query = array();
	public $body = array();

	/**
	 *
	 */
	public function __construct( Client $client, $method, $path ) {
		$this->client = $client;
		$this->method = $method;
		$this->path = $path;
	}

	/**
	 *
	 */
	public function build() {
		if ( !$this->transport ) {
			$url = $this->client->config->url . '/' . $this->apiPath . '/' . $this->path;
			$query = $this->query ? '?' . http_build_query($this->query) : '';

			$class = $this->client->config->getClass('Transport');
			$this->transport = new $class($this->method, $url . $query);
		}
	}

	/**
	 *
	 */
	public function send() {
		// Create transport, with headers
		$this->build();

		// Add headers
		$this->sign();

		// Encode, create HTTP request, send it, receive dumb response
		$body = $this->encode($this->body);
		$response = $this->transport->send($body);

		// Decode response, create smart response object
		$response['response'] = $this->decode($response['body']);

		$class = $this->client->config->getClass('Response');
		return new $class($this, $response);
	}

	/**
	 *
	 */
	public function sign() {
		return $this->client->auth->signRequest($this);
	}

	/**
	 *
	 */
	abstract public function encode( array $body );

	/**
	 *
	 */
	abstract public function decode( $body );

}
