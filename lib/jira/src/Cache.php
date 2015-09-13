<?php

namespace rdx\jira;

use rdx\jira\Client;

interface Cache {

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
