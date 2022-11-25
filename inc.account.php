<?php

class Account {

	public function __construct(
		public string $apiUrl,
		public string $auth,
		public string $username,
		public string $server,
		public bool $active,
	) {}

	public function getServerLabel() : string {
		return parse_url($this->server, PHP_URL_HOST);
	}

	public function getLabel() : string {
		return $this->username . ' @ ' . $this->getServerLabel();
	}

	public function pack() : array {
		return [$this->apiUrl, $this->auth, $this->username, $this->server];
	}

	static public function unpackAll( array $infos ) : array {
		$accounts = [];
		foreach ( $infos AS $i => $info ) {
			$accounts[] = static::unpackOne($info, $i == 0);
		}
		return $accounts;
	}

	static public function fromLogin( string $apiUrl, string $auth, string $username, string $server ) : self {
		return new static($apiUrl, $auth, $username, $server, false);
	}

	static public function unpackOne( array $info, bool $active ) : self {
		return new static($info[0], $info[1], $info[2], $info[3], $active);
	}

}
