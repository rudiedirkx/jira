<?php

class User extends db_generic_record {

	function __construct() {
		if ( !$this->last_sync || $this->last_sync + FORCE_JIRA_USER_SYNC < time() ) {
			// $this->unsync();
		}
	}

	function unsync() {
		global $db;
		$db->delete('filters', array('user_id' => $this->id));
	}

	function get_filters() {
		global $db;
		return $db->select('filters', array('user_id' => $this->id))->all();
	}

	function get_filter_query_options() {
		$filters = $this->filters;
		if ( !$filters ) {
			global $db;

			$filters = jira_get('filter/favourite', null, $error, $info);
			foreach ( $filters AS $filter ) {
				$db->insert('filters', array(
					'user_id' => $this->id,
					'filter_id' => $filter->id,
					'name' => $filter->name,
					'jql' => $filter->jql,
				));
			}

			unset($this->filters);
			$filters = $this->filters;

			$db->update('users', array('last_sync' => time()), array('id' => $this->id));
		}

		$filterOptions = array();
		foreach ( $filters AS $filter ) {
			$filterOptions[$filter->jql] = $filter->name;
		}

		return $filterOptions;
	}

	static function get() {
		global $db;
		return $db->select('users', array('jira_url' => JIRA_URL, 'jira_user' => JIRA_USER), null, 'User')->first();
	}

}
