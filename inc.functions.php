<?php

function do_time($minutes) {
	$hours = floor($minutes / 60);
	$minutes -= $hours * 60;
	return $hours . 'h ' . round($minutes) . 'm';
}

function do_tokencheck() {
	if ( !defined('XSRF_TOKEN') || (string)@$_GET['token'] !== XSRF_TOKEN ) {
		exit("Access denied\n");
	}
}

function html_labels( $labels ) {
	return implode(' ', array_map(function($label) {
		$query = urlencode("labels = '$label' and status not in (Resolved, Closed)");
		return '<a class="label" href="index.php?query=' . html($query) . '">' . html($label) . '</a>';
	}, $labels));
}

function html_icon( $icon, $type = '' ) {
	$url = @$icon->{$type . 'Url'} ?: $icon->iconUrl;
	$name = @$icon->{$type . 'Name'} ?: $icon->name;

	$html = '';
	$html .= '<span class="icon-wrapper">';
	$html .= '<img class="icon ' . $type . '" src="' . $url . '" alt="' . html($name) . '" title="' . html($name) . '" tabindex="-1" />';
	$html .= '<span class="icon-name">' . html($name) . '</span>';
	$html .= '</span>';

	return $html;
}

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

	// Images through proxy
	$regex = '#src="' . preg_quote(JIRA_URL, '#') . '/secure/(?:attachment|thumbnail)/(\d+)/([^"]+)"#';
	$html = preg_replace($regex, 'data-attachment="$1" data-context="markup" data-src="attachment.php?thumbnail=1&id=$1"', $html);
	$regex = '#href="' . preg_quote(JIRA_URL, '#') . '/secure/attachment/(\d+)/([^"]+)"#';
	$html = preg_replace($regex, 'target="_blank" href="attachment.php?id=$1"', $html);

	return $html;
}

function do_markup( $text ) {
	return nl2br(html(trim($text)));
}

function do_encrypt( $data ) {
	$iv_size = openssl_cipher_iv_length('AES-256-CBC');
	$iv = openssl_random_pseudo_bytes($iv_size);
	return base64_encode($iv . openssl_encrypt($data, 'AES-256-CBC', SECRET . SECRET, 0, $iv));
}

function do_decrypt( $data ) {
	$data = base64_decode($data);
	$iv_size = openssl_cipher_iv_length('AES-256-CBC');
	$iv = substr($data, 0, $iv_size);
	$data = substr($data, $iv_size);
	return rtrim(openssl_decrypt($data, 'AES-256-CBC', SECRET . SECRET, 0, $iv), "\0");
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
		if ( basename($_SERVER['PHP_SELF']) != 'auth.php' ) {
			do_redirect('auth');
		}
		exit('<a href="auth.php">Need login</a>' . "\n");
	}
}

function html( $text ) {
	return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function html_q( $change, $stringify = true, $source = null ) {
	$source || $source = $_GET;
	$source = $change + $source;
	$source = array_filter($source, function($value) {
		return $value !== false;
	});
	return $stringify ? $_SERVER['PHP_SELF'] . ($source ? '?' . http_build_query($source) : '') : $source;
}

function html_links( $links ) {
	$html = array();
	foreach ( $links AS $label => $href ) {
		$html[] = '<a href="' . html($href) . '">' . html($label) . '</a>';
	}
	return implode(' | ', $html);
}

function html_options( $options, $selected = null, $empty = '', $sanitize = true ) {
	$html = '';
	$empty && $html .= '<option value="">' . $empty . '</option>';
	foreach ( $options AS $value => $label ) {
		$isSelected = $value == $selected ? ' selected' : '';
		$html .= '<option value="' . html($value) . '"' . $isSelected . '>' . ($sanitize ? html($label) : $label) . '</option>';
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
	$session = jira_get('/rest/auth/1/session', array(), $error, $info);

	$info['session'] = $session;

	// Invalid URL
	if ( $error == 404 ) {
		$info['error2'] = 'Invalid URL (HTTP ' . $error . ')';
		return false;
	}
	// Invalid credentials
	elseif ( $error ) {
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

function jira_curl( $url, $method = '', &$info = null ) {
	empty($GLOBALS['jira_requests']) && $GLOBALS['jira_requests'] = array();
	$GLOBALS['jira_requests'][] = $method . ' ' . $url;

	$auth = $info && @$info['JIRA_AUTH'] ? $info['JIRA_AUTH'] : JIRA_AUTH;

	$info['_start'] = microtime(1);

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

function jira_download( $url ) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, JIRA_AUTH);

	$data = curl_exec($ch);
	curl_close($ch);

	return $data;
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

	$info = curl_getinfo($ch) + $info;
	curl_close($ch);

	global $jira_history;
	$jira_history[] = $info['url'];

	$info['headers'] = jira_http_headers($header);

	$code = $info['http_code'];
	$success = $code >= 200 && $code < 300;
	$invalid_url = $code == 404 && is_int(strpos($info['content_type'], 'text/html'));
	$unauth = $code == 401 || $code == 403;

	if ( $unauth && empty($info['unauth_ok']) ) {
		do_logout(true);
		return do_redirect('accounts');
	}

	$error = $success ? false : $code;

	$info['response'] = $body;
	$info['error'] = '';
	if ( $error ) {
		$info['error'] = ($json = @json_decode($body)) ? $json : null;
	}

	$response = $success ? (strpos($info['content_type'], 'json') !== false ? @json_decode($body) : $body) : false;

	$info['_end'] = microtime(1);
	$info['_time'] = $info['_end'] - $info['_start'];

	return $response;
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
