<?php

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

function html_options( $options, $selected = null ) {
	$html = '';
	foreach ( $options AS $value => $label ) {
		$isSelected = $value == $selected ? ' selected' : '';
		$html .= '<option value="' . html($value) . '"' . $isSelected . '>' . html($label) . '</option>';
	}
	return $html;
}

function jira_curl() {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, JIRA_USER . ':' . JIRA_PASS);
	return $ch;
}

function jira_post( $url, $data, &$error = null, &$info = null ) {
	$url = JIRA_URL . $url;
	$body = json_encode($data);

	$ch = jira_curl();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));

	$response = jira_response($ch, $error, $info);
	$info['request'] = $body;
	return $response;
}

function jira_get( $url, $query = null, &$error = null, &$info = null ) {
	$query = $query ? '?' . http_build_query($query) : '';
	$url = JIRA_URL . $url . $query;

	$ch = jira_curl();
	curl_setopt($ch, CURLOPT_URL, $url);

	return jira_response($ch, $error, $info);
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

	$info['response'] = $info['error'] = '';
	if ( $error ) {
		$info['response'] = $body;
		$info['error'] = ($json = @json_decode($body)) ? $json : null;
	}

	return $success ? json_decode($body) : false;
}

function jira_http_headers( $header ) {
	$headers = array();
	foreach ( explode("\n", $header) AS $line ) {
		@list($name, $value) = explode(':', $line, 2);
		if ( ($name = trim($name)) && ($value = trim($value)) ) {
			$headers[strtolower($name)][] = $value;
		}
	}
	return $headers;
}
