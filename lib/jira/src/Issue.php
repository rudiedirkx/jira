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
	public function upload( $filePath, $fileName = null ) {
		$fileName or $fileName = basename($filePath);

		$request = new Api2Request($this->response->request->client, 'UPLOAD', 'issue/' . $this->key . '/attachments');
		$request->build();
		$request->transport->files['file'] = array($filePath, $fileName);
		$response = $request->send();
		return $response;
	}

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

