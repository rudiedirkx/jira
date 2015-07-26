<?php

namespace rdx\jira;

use rdx\jira\Client;

interface Cache {

	// public $client;

	// public $autoPersist = true;

	/**
	 *
	 */
	public function __construct( Client $client = null );

	/**
	 *
	 */
	public function getFields( $name, &$fromCache );

	/**
	 *
	 */
	public function getCustomFields( $name, &$fromCache );

	/**
	 *
	 */
	public function getCustomFieldMapping( $name, &$fromCache );

	/**
	 *
	 */
	public function persistObject( $name, $value );

	/**
	 *
	 */
	public function __get( $name );

}
