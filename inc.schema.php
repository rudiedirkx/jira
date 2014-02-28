<?php

return array(
	'tables' => array(
		'users' => array(
			'columns' => array(
				'id' => array('pk' => true),
				'jira_url' => array('type' => 'text', 'null' => false),
				'jira_user' => array('type' => 'text', 'null' => false),
				'index_query' => array('type' => 'text'),
				'index_project' => array('type' => 'text'),
				'index_filter' => array('unsigned' => true),
				'last_sync' => array('unsigned' => true),
				'jira_timezone' => array('type' => 'text'),
				'cache__custom_field_ids' => array('type' => 'blob'),
			),
			'indexes' => array(
				'user' => array(
					'unique' => true,
					'columns' => array('jira_url', 'jira_user'),
				),
			),
		),
		'options' => array(
			'columns' => array(
				'user_id' => array('unsigned' => true),
				'name' => array('type' => 'text', 'null' => false),
				'value' => array('type' => 'text'),
			),
			'indexes' => array(
				'option' => array(
					'unique' => true,
					'columns' => array('user_id', 'name'),
				),
			),
		),
		'filters' => array(
			'columns' => array(
				'user_id' => array('unsigned' => true),
				'filter_id' => array('unsigned' => true),
				'name' => array('type' => 'text', 'null' => false),
				'jql' => array('type' => 'text', 'null' => false),
			),
			'indexes' => array(
				'filter' => array(
					'unique' => true,
					'columns' => array('user_id', 'filter_id'),
				),
			),
		),
	),
);

