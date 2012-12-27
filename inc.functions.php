<?php

function html_q($change, $stringify = true, $source = null) {
	$source || $source = $_GET;
	$source = $change + $source;
	return $stringify ? http_build_query($source) : $source;
}

function do_redirect( $path, $query = null ) {
	$fragment = '';
	if ( is_int($p = strpos($path, '#')) ) {
		$fragment = substr($path, $p);
		$path = substr($path, 0, $p);
	}

	$query = $query ? '?' . http_build_query($query) : '';
	$location = $path . '.php' . $query . $fragment;
	header('Location: ' . $location);
	exit;
}

function do_logincheck() {
	if ( !defined('JIRA_URL') ) {
		exit('<a href="auth.php">Need login</a>');
	}
}

function html( $text ) {
	return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function html_links( $links ) {
	$html = array();
	foreach ( $links AS $label => $href ) {
		$html[] = '<a href="' . html($href) . '">' . html($label) . '</a>';
	}
	return implode(' | ', $html);
}

function html_options( $options, $selected = null, $empty = '' ) {
	$html = '';
	$empty && $html .= '<option>' . $empty;
	foreach ( $options AS $value => $label ) {
		$isSelected = $value == $selected ? ' selected' : '';
		$html .= '<option value="' . html($value) . '"' . $isSelected . '>' . html($label);
	}
	return $html;
}

function jira_url( $resource, $query = null ) {
	if ( preg_match('#^https?://#i', $resource) ) {
		$url = $resource;
	}
	else {
		$path = '/' == $resource[0] ? '' : JIRA_API_PATH;
		$url = JIRA_URL . $path . $resource;
	}
	$query && $url .= '?' . http_build_query($query);
	return $url;
}

function jira_curl( $url ) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, JIRA_USER . ':' . JIRA_PASS);
	return $ch;
}

function jira_post( $resource, $data, &$error = null, &$info = null ) {
	$url = jira_url($resource);
	$body = json_encode($data);

	$ch = jira_curl($url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

	$response = jira_response($ch, $error, $info);
	$info['request'] = $body;
	return $response;
}

function jira_get( $resource, $query = null, &$error = null, &$info = null ) {
	$url = jira_url($resource, $query);

	$ch = jira_curl($url);

	return jira_response($ch, $error, $info);
}

function jira_put( $resource, $data, &$error = null, &$info = null ) {
	$url = jira_url($resource);
	$body = json_encode($data);

	$fp = fopen('php://temp/maxmemory:256000', 'w');
	fwrite($fp, $body);
	fseek($fp, 0);

	$ch = jira_curl($url);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($ch, CURLOPT_PUT, true);
	curl_setopt($ch, CURLOPT_INFILE, $fp);
	curl_setopt($ch, CURLOPT_INFILESIZE, strlen($body));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

	$response = jira_response($ch, $error, $info);
	$info['request'] = $body;
	return $response;
}

function jira_response( $ch, &$error = null, &$info = null ) {
	$result = curl_exec($ch);

	@list($header, $body) = explode("\r\n\r\n", $result);

	$info = curl_getinfo($ch);
	curl_close($ch);

	$code = $info['http_code'];
	$success = $code >= 200 && $code < 300;

	$error = $success ? false : $code;

	$info['headers'] = jira_http_headers($header);

	$info['response'] = $body;
	$info['error'] = '';
	if ( $error ) {
		$info['error'] = ($json = @json_decode($body)) ? $json : null;
	}

	return $success ? json_decode($body) : false;
}

function jira_http_headers( $header ) {
	$headers = array();
	foreach ( explode("\n", $header) AS $line ) {
		@list($name, $value) = explode(':', $line, 2);
		if ( ($name = trim($name)) && ($value = trim($value)) ) {
			$headers[strtolower($name)][] = urldecode($value);
		}
	}
	return $headers;
}
