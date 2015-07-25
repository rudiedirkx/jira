<?php

namespace rdx\jira;

use rdx\jira\JiraEntity;
use rdx\jira\Client;
use rdx\jira\Response;

class Issue extends JiraEntity {

	public $id = '';
	public $key = '';
	public $self = '';
	public $expand = '';
	public $fields = array();

	/**
	 *
	 */
	public function __get( $name ) {
		// Getter, cache in $this
		if ( is_callable($method = array($this, 'get_' . $name)) ) {
			return $this->$name = call_user_func($method);
		}

		// Proxy to ->fields, don't cache
		else if ( isset($this->fields[$name]) ) {
			return $this->fields[$name];
		}
	}

}

