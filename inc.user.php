<?php

class User extends db_generic_record {

	function __construct() {
		if ( !$this->last_sync || $this->last_sync + FORCE_JIRA_USER_SYNC < time() ) {
			// $this->unsync();
		}
	}

	function unsync() {
// echo "unsync & uncache\n";
		global $db;

		$db->delete('filters', array('user_id' => $this->id));
		unset($this->filters, $this->filter_query_options);
	}

	function get_filters() {
// echo "get filters\n";
		global $db;

		$filters = $db->select('filters', array('user_id' => $this->id))->all();
		if ( !$filters ) {
// echo "live fetch filters\n";
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
			$filters = $this->get_filters();
		}

		return $filters;
	}

	function get_filter_query_options() {
// echo "get filter options\n";
		$filterOptions = array();
		foreach ( $this->filters AS $filter ) {
			$filterOptions[$filter->jql] = $filter->name;
		}

		return $filterOptions;
	}

	static function load() {
		global $db;

		$username = JIRA_USER;
		$url = JIRA_URL;
		return $db->select('users', array('jira_url' => $url, 'jira_user' => $username), null, 'User')->first();
	}

}
