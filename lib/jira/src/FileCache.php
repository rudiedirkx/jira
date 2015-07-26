<?php

namespace rdx\jira;

use rdx\jira\NoCache;
use rdx\jira\exception\InvalidConfigException;

class FileCache extends NoCache {

	/**
	 *
	 */
	protected function getCacheId( $name ) {
		return sha1($this->client->config->url . ':' . $name);
	}

	/**
	 *
	 */
	protected function getFilePath( $name ) {
		// Create a unique cache id for this Client + object name
		$key = $this->getCacheId($name);

		// Use mandatory configured directory.
		if ( !isset($this->client->config->custom['FileCache']['dir']) ) {
			throw new InvalidConfigException('FileCache.dir');
		}
		$dir = $this->client->config->custom['FileCache']['dir'];
		$file = $dir . '/' . $key . '.json';

		return $file;
	}

	/**
	 *
	 */
	protected function fromFileOrLive( $name, $callback, &$fromCache ) {
		// We'll be storing data in files like `fea91b903e019e9d6e0ef28b1eab9644000863be.json`
		// You can create your own unique combination of Client + object name, and store it anywhere
		$file = $this->getFilePath($name);
		if ( file_exists($file) ) {
			$json = file_get_contents($file);
			if ( $json ) {
				$fields = @json_decode($json, true);
				if ( $fields ) {
					// Let NoCache know this was from cache, so it doesn't persist again
					$fromCache = true;
					return $fields;
				}
			}
		}

		// No or invalid cache, fetch live and try to persist
		return parent::$callback($name, $fromCache);
	}

	/**
	 *
	 */
	public function getFields( $name, &$fromCache ) {
		return $this->fromFileOrLive($name, __FUNCTION__, $fromCache);
	}

	/**
	 *
	 */
	public function getCustomFields( $name, &$fromCache ) {
		return $this->fromFileOrLive($name, __FUNCTION__, $fromCache);
	}

	/**
	 *
	 */
	public function getCustomFieldMapping( $name, &$fromCache ) {
		return $this->fromFileOrLive($name, __FUNCTION__, $fromCache);
	}

	/**
	 *
	 */
	public function persistObject( $name, $value ) {
		$file = $this->getFilePath($name);
		if ( is_writable($file) || is_writable(dirname($file)) ) {
			return file_put_contents($file, json_encode($value));
		}
	}

}
