<?php

namespace rdx\jira;

class Config {

	public $url = '';
	public $classes = array();
	public $custom = array();

	/**
	 *
	 */
	public function __construct( $url, array $options = array() ) {
		$this->url = $url;

		$classes = $custom = array();
		foreach ( $options as $class => $config ) {
			$config = (array) $config;

			// Override class name
			if ( isset($config[0]) ) {
				if ( is_string($config[0]) ) {
					$classes[$class] = $config[0];
				}
				unset($config[0]);
			}

			// Custom config
			$custom[$class] = $config;
		}

		$this->classes = $classes + array(
			// Core
			'Transport' => 'rdx\jira\CurlTransport',
			'Request' => 'rdx\jira\Api2Request',
			'Response' => 'rdx\jira\Response',

			// Features
			'Issue' => 'rdx\jira\Issue',
			'User' => 'rdx\jira\User',
		);

		$this->custom = $custom;
	}

	/**
	 *
	 */
	public function getClass( $alias ) {
		$class = $this->classes[$alias];
		return $class;
	}

}
