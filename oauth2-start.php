<?php

require 'inc.bootstrap.php';

$startUrl = 'https://auth.atlassian.com/authorize?' . http_build_query([
	'audience' => 'api.atlassian.com',
	'client_id' => OAUTH_CLENT_ID,
	'scope' => implode(' ', OAUTH_CLENT_SCOPES),
	'redirect_uri' => OAUTH_REDIRECT_URL,
	'state' => rand(),
	'response_type' => 'code',
	'prompt' => 'consent',
]);

header('Location: ' . $startUrl);
echo "Redirecting to $startUrl\n";
exit;
