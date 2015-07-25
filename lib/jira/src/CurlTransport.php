<?php

namespace rdx\jira;

class CurlTransport extends Transport {

	public $curl;

	/**
	 *
	 */
	protected function _build() {
		if ( !$this->curl ) {
			$this->curl = curl_init();
			curl_setopt($this->curl, CURLOPT_URL, $this->url);
			curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->curl, CURLOPT_HEADER, true);
			curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
		}
	}

	/**
	 *
	 */
	protected function _send() {
		// Send headers & body
		$method = $this->method;
		$this->$method();

		// Receive all of it
		$result = curl_exec($this->curl);

		// Parse to HEAD vs BODY level
		@list($this->responseHeader, $this->responseBody) = explode("\r\n\r\n", $result, 2);

		// 100 Continue requires another parse
		if ( is_int(strpos($this->responseHeader, '100 Continue')) ) {
			@list($this->responseHeader, $this->responseBody) = explode("\r\n\r\n", $this->responseBody, 2);
		}
	}

	/**
	 *
	 */
	protected function sendHeaders() {
		$headers = array();
		foreach ($this->headers as $name => $values) {
			foreach ($values as $value) {
				$headers[] = $name . ': ' . $value;
			}
		}
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
	}

	/**
	 *
	 */
	protected function GET() {
		// curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');

		$this->sendHeaders();
	}

	/**
	 *
	 */
	protected function POST() {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);

		$this->sendHeaders();
	}

	/**
	 *
	 */
	protected function PUT() {

	}

	/**
	 *
	 */
	protected function DELETE() {

	}

	/**
	 *
	 */
	protected function _receive() {
		$this->responseInfo = curl_getinfo($this->curl);
		curl_close($this->curl);

		$this->response['code'] = (int)$this->responseInfo['http_code'];

		$headers = array();
		foreach ( explode("\n", $this->responseHeader) AS $n => $line ) {
			if ( $n == 0 ) {
				list(, , $status) = explode(' ', trim($line), 3);
				$this->response['status'] = $status;
			}
			else {
				list($name, $value) = explode(':', $line, 2);
				if ( ($name = trim($name)) && ($value = trim($value)) ) {
					$headers[strtolower($name)][] = urldecode($value);
				}
			}
		}
		$this->response['headers'] = $headers;

		$this->response['body'] = $this->responseBody;

		$this->response['info'] = $this->responseInfo;
	}

}
