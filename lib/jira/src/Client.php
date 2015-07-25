<?php

namespace rdx\jira;

use rdx\jira\Config;
use rdx\jira\Auth;

class Client {

	public $config;
	public $auth;

	/**
	 *
	 */
	public function __construct(Config $config, Auth $auth) {
		$this->config = $config;
		$this->auth = $auth;
	}

	/**
	 *
	 */
	protected function request( $method, $path, $query = array() ) {
		$class = $this->config->getClass('Request');
		$request = new $class($this, $method, $path);
		$request->query = $query;
		return $request;
	}

	/**
	 *
	 */
	public function get( $path, $query = array() ) {
		return $this->request('GET', $path, $query);
	}

	/**
	 *
	 */
	public function open( $id, array $options = array() ) {
		$options += array(
			'expand' => array(),
		);
		$request = $this->get('issue/' . $id);
		$response = $request->send();

		$class = $this->config->getClass('Issue');
		return new $class($this, $response);
	}

	/**
	 *
	 */
	public function user( $name ) {
		$request = $this->get('user', array('username' => $name));
		$response = $request->send();

		$class = $this->config->getClass('User');
		return new $class($this, $response);
	}

}
