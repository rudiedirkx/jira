<?php

namespace rdx\jira;

use rdx\jira\JiraEntity;
use rdx\jira\Client;
use rdx\jira\Response;

class User extends JiraEntity {

	public $key = '';
	public $name = '';
	public $emailAddress = '';
	public $avatarUrls = array();
	public $displayName = '';
	public $active = '';
	public $timeZone = '';
	public $groups = array();
	public $self = '';
	public $expand = '';

}
