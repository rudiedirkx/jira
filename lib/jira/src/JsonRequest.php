<?php

namespace rdx\jira;

class JsonRequest extends Request {

	/**
	 *
	 */
	public function encode( array $body ) {
		return '[]';
	}

	/**
	 *
	 */
	public function decode( $body ) {
		return json_decode($body, true);
	}

}
