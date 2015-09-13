<?php

namespace rdx\jira;

class NoCache implements Cache {

	public $client;

	public $autoPersist = true;

	/**
	 *
	 */
	public function getFields( $name, &$fromCache ) {
		$request = new Api2Request($this->client, 'GET', 'field');
		$response = $request->send();
		return $response->response;
	}

	/**
	 *
	 */
	public function getCustomFields( $name, &$fromCache ) {
		$custom = array_filter($this->fields, function($field) {
			return !empty($field['custom']);
		});

		return array_values($custom);
	}

	/**
	 *
	 */
	public function getCustomFieldMapping( $name, &$fromCache ) {
		$mapping = array();
		foreach ( $this->custom_fields as $field ) {
			$key = strtolower(trim(preg_replace('#[^\w\d]+#', '_', $field['name']), '_'));
			$mapping[$key] = $field['id'];
		}

		return $mapping;
	}

	/**
	 *
	 */
	public function persistObject( $name, $value ) {
		// Nowhere to save it to, but rdx\jira\Cache demands this method exists.
	}

	/**
	 *
	 */
	public function __get( $name ) {
		$this->$name = null;

		if ( is_callable($method = array($this, 'get' . str_replace('_', '', $name))) ) {
			$fromCache = false;
			$args = array($name, &$fromCache);
			$this->$name = call_user_func_array($method, $args);

			// Persist automatically, value is truthy and value is live/non-cache
			if ( $this->autoPersist && $this->$name && !$fromCache ) {
				$this->persistObject($name, $this->$name);
			}
		}

		return $this->$name;
	}

}
