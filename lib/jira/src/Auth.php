<?php

namespace rdx\jira;

use rdx\jira\Request;

interface Auth {

	/**
	 *
	 */
	public function signRequest( Request $request );

}
