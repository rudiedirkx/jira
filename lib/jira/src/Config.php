<?php

namespace rdx\jira;

class Config {

	public $url = '';
	public $options = array();

	/**
	 *
	 */
	public function __construct( $url, array $options = array() ) {
		$this->url = $url;

		$this->options = $options + array(
			// Core
			'Transport' => 'rdx\jira\CurlTransport',
			'Request' => 'rdx\jira\Api2Request',
			'Response' => 'rdx\jira\Response',

			// Features
			'Issue' => 'rdx\jira\Issue',
			'User' => 'rdx\jira\User',
		);
	}

	/**
	 *
	 */
	public function getClass( $alias ) {
		$class = $this->options[$alias];
		return $class;
	}

}
