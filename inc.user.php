<?php

class User extends db_generic_record {

	public static $_config = array(
		'index_page_size' => array(
			'label' => 'Index page size',
			'default' => 10,
			'size' => 4,
			'type' => 'number',
		),
	);

	function __construct() {
		if ( !$this->last_sync || $this->last_sync + FORCE_JIRA_USER_SYNC < time() ) {
			// $this->unsync();
		}
	}

	function config( $name, $alt = null ) {
		if ( func_num_args() == 1 ) {
			$alt = $this::$_config[$name]['default'];
		}

		$config = $this->config;
		return isset($config[$name]) ? $config[$name] : $alt;
	}

	function get_config() {
		global $db;
		return $db->select_fields('options', 'name, value', array('user_id' => $this->id));
	}

	function get_jira_domain() {
		$url = parse_url($this->jira_url);
		return $url['host'];
	}

	function get_jira_id() {
		return $this->jira_user . '@' . $this->jira_domain;
	}

	function get_variables() {
		global $db;
		return $db->select('variables', array('user_id' => $this->id))->all();
	}

	function get_custom_fields() {
		$fields = jira_get('field');
		return array_values(array_filter($fields, function($f) {
			return @$f->custom;
		}));
	}

	function get_custom_field_ids() {
		if ( !$this->cache__custom_field_ids ) {
			$fields = array();
			foreach ($this->custom_fields as $field) {
				$fields[mb_strtolower($field->name)] = $field->id;
			}
			$this->cache__custom_field_ids = serialize($fields);
			$this->save(array('cache__custom_field_ids' => $this->cache__custom_field_ids));
		}

		return unserialize($this->cache__custom_field_ids);
	}

	function unsync() {
// echo "unsync & uncache\n";
		global $db;

		$db->delete('filters', array('user_id' => $this->id));
		unset($this->filters, $this->filter_query_options);

		$this->save(array('cache__custom_field_ids' => $this->cache__custom_field_ids = ''));
		unset($this->filters, $this->filter_query_options, $this->custom_fields, $this->custom_field_ids);
	}

	function get_filters() {
// echo "get filters\n";
		global $db;

		$filters = $db->select('filters', 'user_id = ? ORDER BY name ASC', array($this->id))->all();
		if ( !$filters ) {
// echo "live fetch filters\n";
			$jira_filters = jira_get('filter/favourite', null, $error, $info);

			// Error, no need to prettify
			if ( $error ) {
				var_dump($error, $info);
				exit;
			}

			// No error, but no filters, so return early
			if ( !$jira_filters ) {
				return array();
			}

			// Save filters in local db
			foreach ( $jira_filters AS $filter ) {
				$db->insert('filters', array(
					'user_id' => $this->id,
					'filter_id' => $filter->id,
					'name' => $filter->name,
					'jql' => $filter->jql,
				));
			}

			// Re-fetch local filters and save into ->filters via __get magic
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

	function _applyVariables( $variables ) {
		$vars = $this->variables;
		return array_map(function($jql) use ($vars) {
			foreach ( $vars as $var ) {
				$jql = preg_replace('#' . $var->regex . '#', str_replace('XXX', $var->value, $var->replacement), $jql);
			}
			return $jql;
		}, $variables);
	}

	function get_filter_query_options() {
		return array_flip($this->_applyVariables($this->get_filter_options('name', 'jql')));
	}

	function get_index_filter_object() {
		if ( $this->index_filter ) {
			global $db;
			$filter = $db->select('filters', array('user_id' => $this->id, 'filter_id' => $this->index_filter))->first();

			$realJQL = $this->_applyVariables(array($filter->jql));
			$filter->jql = $realJQL[0];

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
