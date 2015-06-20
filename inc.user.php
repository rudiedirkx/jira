<?php

class User extends db_generic_record {

	public static $_config = array(
		'index_page_size' => array(
			'label' => 'Index page size',
			'default' => 10,
			'size' => 4,
			'type' => 'number',
			'required' => true,
		),
		'agile_view_id' => array(
			'label' => 'Agile view/board ID',
			'default' => '',
			'size' => 4,
			'type' => 'number',
			'required' => false,
		),
		'load_epics' => array(
			'label' => 'Load epics (more requests, but more info)',
			'default' => 0,
			'size' => 1,
			'type' => 'checkbox',
			'required' => false,
		),
	);

	function __construct() {
		if ( !$this->last_sync || $this->last_sync + FORCE_JIRA_USER_SYNC < time() ) {
			// $this->unsync();
		}

		$this->syncOverdueVars();
	}

	function getActiveSprint( $boardId = null ) {
		$boardId or $boardId = $this->config('agile_view_id');
		if ( !$boardId ) {
			return;
		}

		$sprints = jira_get('/rest/greenhopper/1.0/sprintquery/' . $boardId, array(), $error, $info);
		$actives = array_filter($sprints->sprints, function($sprint) {
			return $sprint->state == 'ACTIVE';
		});
		if ( !$actives ) {
			return;
		}

		return reset($actives);
	}

	function getAutoVarSprint() {
		global $db;

		$sprint = $this->getActiveSprint();
		if ( $sprint ) {
			return $sprint->id;
		}
	}

	function syncAnyAutoVar( $type, $id = null ) {
		global $db;

		if ( !$id ) {
			$id = $db->select_one('variables', 'id', array('user_id' => $this->id, 'auto_update_type' => 'sprint'));
		}

		$function = 'getAutoVar' . $type;
		if ( method_exists($this, $function) ) {
			$value = $this->$function();
			if ( $value ) {
				$db->update('variables', array(
					'value' => $value,
					'last_update' => time(),
				), array(
					'id' => $id,
				));
			}
		}
	}

	function syncOverdueVars() {
		foreach ( $this->overdue_vars as $id => $type ) {
			$this->syncAnyAutoVar($type, $id);
		}
	}

	function get_overdue_vars() {
		global $db;
		return $db->select_fields('variables', 'id, auto_update_type', '
			user_id = ? AND
			auto_update_type <> ? AND
			last_update < ?
		', array(
			$this->id,
			'',
			time() - FORCE_AUTO_VARS_SYNC,
		));
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
			foreach ( $this->custom_fields as $field ) {
				$fields[mb_strtolower($field->name)] = $field->id;
			}
			$this->cache__custom_field_ids = serialize($fields);
			$this->save(array('cache__custom_field_ids' => $this->cache__custom_field_ids));
		}

		return unserialize($this->cache__custom_field_ids);
	}

	function get_cf_story_points() {
		return @$this->custom_field_ids['story points'] ?: '';
	}

	function get_cf_epic_link() {
		return @$this->custom_field_ids['epic link'] ?: '';
	}

	function get_cf_epic_name() {
		return @$this->custom_field_ids['epic name'] ?: '';
	}

	function get_cf_epic_status() {
		return @$this->custom_field_ids['epic status'] ?: '';
	}

	function get_cf_epic_color() {
		return @$this->custom_field_ids['epic colour'] ?: @$this->custom_field_ids['epic color'] ?: '';
	}

	function unsync() {
		global $db;

		$db->delete('filters', array('user_id' => $this->id));
		unset($this->filters, $this->filter_query_options);

		$this->save(array('cache__custom_field_ids' => $this->cache__custom_field_ids = ''));
		unset($this->custom_fields, $this->custom_field_ids);
	}

	function get_filters() {
		global $db;

		$filters = $db->select('filters', 'user_id = ? ORDER BY name ASC', array($this->id))->all();
		if ( !$filters ) {
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

	static function load( $url = null, $username = null ) {
		global $db;

		$url or $url = JIRA_URL;
		$username or $username = JIRA_USER;

		return $db->select('users', array(
			'jira_url' => $url,
			'jira_user' => $username,
		), null, 'User')->first();
	}

}
