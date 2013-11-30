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

		$filters = $db->select('filters', 'user_id = ? ORDER BY name ASC', array($this->id))->all();
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

	function get_filter_options( $valueField = 'filter_id', $labelField = 'name' ) {
		$filterOptions = array();
		foreach ( $this->filters AS $filter ) {
			$filterOptions[$filter->$valueField] = $filter->$labelField;
		}

		return $filterOptions;
	}

	function get_filter_options_jql() {
		return $this->get_filter_options('filter_id', 'jql');
	}

	function get_filter_query_options() {
		return $this->get_filter_options('jql');
	}

	function get_index_filter_object() {
		if ( $this->index_filter ) {
			global $db;
			$filter = $db->select('filters', array('user_id' => $this->id, 'filter_id' => $this->index_filter))->first();
			if ( $filter ) {
				return $filter;
			}
		}

		return false;
	}

	function save( $updates ) {
		global $db;
		return $db->update('users', $updates, array('id' => $this->id));
	}

	static function load() {
		global $db;

		$username = JIRA_USER;
		$url = JIRA_URL;
		return $db->select('users', array('jira_url' => $url, 'jira_user' => $username), null, 'User')->first();
	}

}
