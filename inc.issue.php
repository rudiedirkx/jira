<?php

class Issue extends db_generic_record {

	public static function map( $issues ) {
		return array_map(function($issue) {
			return new Issue($issue);
		}, $issues);
	}

	protected $_got = array();

	public function __construct( $issue ) {
		foreach ( get_object_vars($issue) as $name => $value ) {
			$this->$name = $value;
		}
	}



	/**
	 *
	 */

	public function get_transitions() {
		return array();
	}



	/**
	 * Sub tasks
	 */

	public function get_subtasks() {
		return self::map(@$this->fields->subtasks ?: array());
	}

	public function get_subkeys() {
		return array_map(function($issue) {
			return $issue->key;
		}, $this->subtasks);
	}

	/**
	 * Parent issue
	 */

	public function get_parent() {
		$parent = @$this->fields->parent;
		return $parent ? new Issue($parent) : null;
	}

	/**
	 * Timestamps
	 */

	public function get_created() {
		return strtotime($this->fields->created);
	}

	public function get_updated() {
		return strtotime($this->fields->updated);
	}

	/**
	 * Simple child objects
	 */

	public function get_attachments() {
		$attachments = @$this->fields->attachment ?: array();
		usort($attachments, function($a, $b) {
			return strtotime($a->created) - strtotime($b->created);
		});
		return $attachments;
	}

	public function get_worklogs() {
		return @$this->fields->worklog ?: (object) array(
			'total' => 0,
			'worklogs' => array(),
		);
	}

	public function get_links() {
		return @$this->fields->issuelinks ?: array();
	}

	public function get_comments() {
		return @$this->fields->comment->comments ?: array();
	}

	/**
	 * Parent EPIC
	 */

	public function get_parent_epic_key() {
		global $user;
		return $user->cf_epic_link ? @$this->fields->{$user->cf_epic_link} : null;
	}

	public function get_parent_epic() {
		global $user;
		if ( $this->parent_epic_key && $user->config('load_epics') ) {
			$parentEpic = jira_get('issue/' . $this->parent_epic_key);
			if ( $parentEpic ) {
				return new Issue($parentEpic);
			}
		}
	}

	// public function get_parent_epic_color() {
	// 	if ( $this->parent_epic ) {
	// 		return @$this->parent_epic->self_epic->color;
	// 	}
	// }

	/**
	 * Self EPIC
	 */

	public function get_self_epic() {
		global $user;
		if ( $user->cf_epic_name && $user->cf_epic_status && $user->cf_epic_link ) {
			if ( @$this->fields->{$user->cf_epic_name} && @$this->fields->{$user->cf_epic_status} ) {
				$epic = (object) array(
					'name' => $this->fields->{$user->cf_epic_name},
					'status' => $this->fields->{$user->cf_epic_status},
					'color' => '',
				);
				if ( $user->cf_epic_color && @$this->fields->{$user->cf_epic_color} ) {
					$epic->color = $this->fields->{$user->cf_epic_color};
				}

				return $epic;
			}
		}
	}

	public function get_self_epic_label() {
		if ( $this->self_epic ) {
			return '<span class="epic ' . html($this->self_epic->color) . '">' . html($this->self_epic->name) . '</span>';
		}
	}

	public function get_self_epic_issues() {
		if ( $this->self_epic ) {
			$query = '"Epic Link" = ' . $this->key . ' ORDER BY Rank';
			$issues = jira_get('search', array('jql' => $query), $error, $info);
			if ( !$error && !empty($issues->issues) ) {
				return self::map($issues->issues);
			}
		}
	}

	/**
	 * User story
	 */

	public function get_story_points() {
		global $user;
		return $user->cf_story_points ? (int) @$this->fields->{$user->cf_story_points} : 0;
	}



	/**
	 * Util
	 */

	public function __unget() {
		foreach ( $this->_got as $name ) {
			unset($this->$name);
		}
	}

	public function &__get( $name ) {
		$this->_got[] = $name;
		return parent::__get($name);
	}

}
