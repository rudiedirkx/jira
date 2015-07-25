<?php

namespace rdx\jira;

abstract class Transport {

	public $url = '';
	public $method = '';
	public $headers = array(
		'User-agent' => array('rdx\\jira 2.0'),
	);
	public $body = '';

	public $response = array(
		'code' => 0,
		'status' => '',
		'headers' => array(),
		'body' => '',
	);

	/**
	 *
	 */
	public function __construct( $method,  $url ) {
		$this->method = strtoupper($method);
		$this->url = $url;
	}

	/**
	 *
	 */
	public function setHeader( $name, $value ) {
		$this->headers[$name] = array($value);
	}

	/**
	 *
	 */
	public function addHeader( $name, $value ) {
		isset($this->headers[$name]) or $this->headers[$name] = array();

		$this->headers[$name][] = $value;
	}

	/**
	 *
	 */
	public function send( $body ) {
		$this->body = in_array($this->method, array('POST', 'PUT')) ? $body : '';

		$this->_build();
		$this->_send();
		$this->_receive();

		return $this->response;
	}

	/**
	 *
	 */
	abstract protected function GET();

	/**
	 *
	 */
	abstract protected function POST();

	/**
	 *
	 */
	abstract protected function PUT();

	/**
	 *
	 */
	abstract protected function DELETE();

	/**
	 *
	 */
	abstract protected function _build();

	/**
	 *
	 */
	abstract protected function _send();

	/**
	 *
	 */
	abstract protected function _receive();

}
