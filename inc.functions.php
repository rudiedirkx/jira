<?php

function get_urls( $accounts = null ) {
	if ( $accounts ) {
		return array_map(function($acc) {
			return $acc->url;
		}, $accounts);
	}

	$urls = @$_COOKIE['JIRA_URL'] ? explode(',', $_COOKIE['JIRA_URL']) : array();
	return $urls;
}

function get_auths( $accounts = null ) {
	if ( $accounts ) {
		return array_map(function($acc) {
			return $acc->auth;
		}, $accounts);
	}

	$auths = @$_COOKIE['JIRA_AUTH'] ? explode(',', $_COOKIE['JIRA_AUTH']) : array();
	return array_map('do_decrypt', $auths);
}

function get_accounts() {
	$urls = get_urls();
	$auths = get_auths();

	$accounts = array();
	foreach ( $auths AS $i => $auth ) {
		list($user) = explode(':', $auth, 2);
		$accounts[] = (object)array(
			'url' => $urls[$i],
			'auth' => $auth,
			'user' => $user,
			'active' => !$i,
		);
	}

	return $accounts;
}

function do_logout( $layered = false ) {
	$accounts = get_accounts();

	// Peel off first layer (active account)
	if ( $layered && isset($accounts[1]) ) {
		unset($accounts[0]);
		do_login('', '', $accounts);
	}
	// Log out completely
	else {
		// Unset AUTH
		setcookie('JIRA_AUTH', '', 1);
		unset($_COOKIE['JIRA_AUTH']);

		// Reset URL
		if ( $accounts && $_COOKIE['JIRA_URL'] != $accounts[0]->url ) {
			$expire = strtotime('+6 months');
			$_COOKIE['JIRA_URL'] = $accounts[0]->url;
			setcookie('JIRA_URL', $_COOKIE['JIRA_URL'], $expire);
		}
	}
}

function do_login( $url, $auth, $accounts = null ) {
	$accounts or $accounts = get_accounts();

	if ($url && $auth) {
		$accounts[] = (object)compact('url', 'auth');
	}

	$urls = get_urls($accounts);
	$auths = array_map('do_encrypt', get_auths($accounts));

	$expire = strtotime('+6 months');
	$_COOKIE['JIRA_URL'] = implode(',', $urls);
	setcookie('JIRA_URL', $_COOKIE['JIRA_URL'], $expire);
	$_COOKIE['JIRA_AUTH'] = implode(',', $auths);
	setcookie('JIRA_AUTH', $_COOKIE['JIRA_AUTH'], $expire);
}

function do_remarkup( $html ) {
	$html = trim($html);

	// Links to other issues
	$regex = preg_quote(JIRA_URL, '#') . '/browse/([A-Z][A-Z\d]+\-\d+)';
	$html = preg_replace_callback('#' . $regex . '#', function($match) {
		$key = $match[1];
		return 'issue.php?key=' . $key;
	}, $html);

	// Non-full paths
	$html = str_replace('="/', '="' . JIRA_ORIGIN . '/', $html);

	return $html;
}

function do_markup( $text ) {
	return nl2br(html(trim($text)));
}

function do_encrypt( $data ) {
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	return base64_encode($iv . mcrypt_encrypt(MCRYPT_RIJNDAEL_256, substr(SECRET . SECRET, 0, 24), $data, MCRYPT_MODE_CBC, $iv));
}

function do_decrypt( $data ) {
	$data = base64_decode($data);
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
	$iv = substr($data, 0, $iv_size);
	$data = substr($data, $iv_size);
	return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, substr(SECRET . SECRET, 0, 24), $data, MCRYPT_MODE_CBC, $iv), "\0");
}

function do_redirect( $path, $query = null ) {
	$fragment = '';
	if ( is_int($p = strpos($path, '#')) ) {
		$fragment = substr($path, $p);
		$path = substr($path, 0, $p);
	}

	$query = $query ? '?' . http_build_query($query) : '';
	$location = $path . '.php' . $query . $fragment;
// var_dump($location);
// exit;
	header('Location: ' . $location);
	exit;
}

function do_logincheck() {
	if ( !defined('JIRA_AUTH') ) {
		exit('<a href="auth.php">Need login</a>');
	}
}

function html( $text ) {
	return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function html_q( $change, $stringify = true, $source = null ) {
	$source || $source = $_GET;
	$source = $change + $source;
	return $stringify ? http_build_query($source) : $source;
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
	$empty && $html .= '<option value="">' . $empty . '</option>';
	foreach ( $options AS $value => $label ) {
		$isSelected = $value == $selected ? ' selected' : '';
		$html .= '<option value="' . html($value) . '"' . $isSelected . '>' . html($label) . '</option>';
	}
	return $html;
}

function jira_test( $url, $user, $pass, &$info = null ) {
	// Test connection
	$info = array(
		'unauth_ok' => 1,
		'JIRA_URL' => $url,
		'JIRA_AUTH' => $user . ':' . $pass,
	);
	$account = jira_get('user', array('username' => $user), $error, $info);
	$info['account'] = $account;

	// Invalid URL
	if ( $error == 404 ) {
		$info['error2'] = 'Invalid URL (HTTP ' . $error . ')';
		return false;
	}
	// Invalid credentials
	else if ( $error || empty($account->active) || empty($account->name) || $account->name !== $user ) {
		$info['error2'] = 'Invalid login (HTTP ' . $error . ')';
		return false;
	}

	return true;
}

function jira_url( $resource, $query = null, $info = null ) {
	if ( preg_match('#^https?://#i', $resource) ) {
		$url = $resource;
	}
	else {
		$baseUrl = $info && @$info['JIRA_URL'] ? $info['JIRA_URL'] : JIRA_URL;
		$path = '/' == $resource[0] ? '' : JIRA_API_PATH;
		$url = $baseUrl . $path . $resource;
	}
	$query && $url .= '?' . http_build_query($query);
	return $url;
}

function jira_curl( $url, $method = '', $info = null ) {
	empty($GLOBALS['jira_requests']) && $GLOBALS['jira_requests'] = array();
	$GLOBALS['jira_requests'][] = $method . ' ' . $url;

	$auth = $info && @$info['JIRA_AUTH'] ? $info['JIRA_AUTH'] : JIRA_AUTH;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, $auth);
	return $ch;
}

function jira_post( $resource, $data, &$error = null, &$info = null ) {
	$_start = microtime(1);

	$url = jira_url($resource, null, $info);
	$body = json_encode($data);

	$ch = jira_curl($url, 'POST', $info);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json', 'User-agent: Jira Mobile'));

	$response = jira_response($ch, $error, $info);
	$info['request'] = $body;

	$_time = microtime(1) - $_start;
	jira_log('POST', $resource, $_time, $info);

	return $response;
}

function jira_upload( $resource, $data, &$error = null, &$info = null ) {
	$_start = microtime(1);

	$url = jira_url($resource, null, $info);

	$ch = jira_curl($url, 'POST', $info);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Atlassian-Token: nocheck', 'User-agent: Jira Mobile'));

	$response = jira_response($ch, $error, $info);
	$info['request'] = $data;

	$_time = microtime(1) - $_start;
	jira_log('POST', $resource, $_time, $info);

	return $response;
}

function jira_get( $resource, $query = null, &$error = null, &$info = null ) {
	$_start = microtime(1);

	$url = jira_url($resource, $query, $info);

	$ch = jira_curl($url, 'GET', $info);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-agent: Jira Mobile'));

	$response = jira_response($ch, $error, $info);

	$_time = microtime(1) - $_start;
	jira_log('GET', $resource, $_time, $info);

	return $response;
}

function jira_put( $resource, $data, &$error = null, &$info = null ) {
	$_start = microtime(1);

	$url = jira_url($resource, null, $info);
	$body = json_encode($data);

	$fp = fopen('php://temp/maxmemory:256000', 'w');
	fwrite($fp, $body);
	fseek($fp, 0);

	$ch = jira_curl($url, 'PUT', $info);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($ch, CURLOPT_PUT, true);
	curl_setopt($ch, CURLOPT_INFILE, $fp);
	curl_setopt($ch, CURLOPT_INFILESIZE, strlen($body));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json', 'User-agent: Jira Mobile'));

	$response = jira_response($ch, $error, $info);
	$info['request'] = $body;

	$_time = microtime(1) - $_start;
	jira_log('GET', $resource, $_time, $info);

	return $response;
}

function jira_delete( $resource, $query = null, &$error = null, &$info = null ) {
	$_start = microtime(1);

	$url = jira_url($resource, $query, $info);

	$ch = jira_curl($url, 'DELETE', $info);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-agent: Jira Mobile'));

	$response = jira_response($ch, $error, $info);

	$_time = microtime(1) - $_start;
	jira_log('GET', $resource, $_time, $info);

	return $response;
}

function jira_response( $ch, &$error = null, &$info = null ) {
	$result = curl_exec($ch);

	@list($header, $body) = explode("\r\n\r\n", $result, 2);

	// OMG HTTP 100 Continue... You suck!
	if ( is_int(strpos($header, '100 Continue')) ) {
		@list($header, $body) = explode("\r\n\r\n", $body, 2);
	}

	$params = $info;

	$info = curl_getinfo($ch);
	curl_close($ch);

	$info['headers'] = jira_http_headers($header);

	$code = $info['http_code'];
	$success = $code >= 200 && $code < 300;
	$invalid_url = $code == 404 && is_int(strpos($info['content_type'], 'text/html'));
	$unauth = $code == 401 || $code == 403 /*|| $invalid_url*/;

	if ( $unauth && empty($params['unauth_ok']) ) {
		global $db;
		$db->delete('users', array('jira_url' => JIRA_URL, 'jira_user' => JIRA_USER));
		do_logout(true);
		return do_redirect('accounts');
	}

	$error = $success ? false : $code;

	$info['response'] = $body;
	$info['error'] = '';
	if ( $error ) {
		$info['error'] = ($json = @json_decode($body)) ? $json : null;
	}

	return $success ? @json_decode($body) : false;
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

function jira_log( $method, $resource, $time, $info = null ) {
	if ( !defined('DEBUG') || !DEBUG ) return;

	static $fh;
	if ( !$fh ) {
		$fh = fopen(dirname(DB_PATH) . '/jira.log', 'a');
		fwrite($fh, "\n==\n");
	}

	$log = number_format($time, 4) . ' ' . strtoupper($method) . ' ' . $resource;
	fwrite($fh, $log . "\n");
}
