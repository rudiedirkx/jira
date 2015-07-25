<?php

namespace rdx\jira;

use rdx\jira\Request;

class NoAuth implements Auth {

	/**
	 *
	 */
	public function signRequest( Request $request ) {
		$request->build();

		// Don't add anything...
	}

}
