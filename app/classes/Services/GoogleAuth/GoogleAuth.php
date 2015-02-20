<?php

namespace Services\GoogleAuth;

class GoogleAuth {

	const DISCOVERY_URL = "https://accounts.google.com/.well-known/openid-configuration";
	private $endpoints, $clientid, $secret, $session, $options;

	function __construct(&$sessionhandler, $clientid, $secret, $options = array()) {
		$this->session =& $sessionhandler;
		$this->clientid = $clientid;
		$this->secret = $secret;
		$this->options = array_merge(array(
			'canceldest' => '/',
			'callback' => 'https://'.$_SERVER['HTTP_HOST'].'/auth/callback'
		), $options);
	}

	function authenticate($doredirect = true) {
		if (isset($this->session['user'])) {
			return $this->session['user'];
		} else {
			if ($doredirect) {
				$url = $this->getAuthRedirectUrl();
				header("Location: ".$url);
			} else {
				return null;
			}
			exit;
		}
	}

	public function getAuthRedirectUrl() {
		$this->determineEndPoints();
		$this->session["originalurl"] = $_SERVER['REQUEST_URI'];
		$this->session["csrf"] = md5(rand());
		$url = $this->endpoints['authorization_endpoint']."?".http_build_query(array(
			"client_id" => $this->clientid,
			"response_type" => "code",
			"scope" => "openid email",
			"redirect_uri" => $this->options['callback'],
			"state" => $this->session['csrf']
		));
		return $url;
	}

	public function receiveCallback($params) {
		$this->determineEndPoints();

		// Validate state
		if (empty($params['state']) or $params['state'] !== $this->session['csrf']) {
			throw new \Exception("CSRF check failed");
		}

		if (!empty($params['error'])) {
			if ($params['error'] === 'access_denied') {
				header('Location: '.$this->options['canceldest']);
			} else {
				throw new \Exception($params['error']);
			}
		}

		if (empty($params['code'])) {
			throw new \Exception("Missing token code");
		}

		$request = new \HTTP\HTTPRequest($this->endpoints['token_endpoint']);
		$request->setMethod('POST');
		$request->setPostEncoding('form');
		$request->set(array(
			'code'=>$params['code'],
			'client_id'=>$this->clientid,
			'client_secret'=>$this->secret,
			'redirect_uri'=>$this->options['callback'],
			'grant_type'=>'authorization_code'
		));
		$response = $request->send();
		$data = @json_decode($response->getBody(), true);
		if (empty($data['id_token'])) {
			throw new \Exception("Failed to exchange code for id_token");
		}

		// Verifying sig on this token unnecesary (https://developers.google.com/accounts/docs/OpenIDConnect#obtainuserinfo)
		list($header, $data, $sig) = explode('.', $data['id_token']);
		$data = @json_decode(base64_decode($data), true);

		// Get the email address
		list($username, $domain) = explode('@', $data['email']);
		$doman = strtolower($domain);

		// Canonicalise GMail addresses: googlemail and gmail are the same,
		// dots in username are ignored, as is anything after a +
		if ($domain == 'googlemail.com') $domain = 'gmail.com';
		if ($domain == 'gmail.com') {
			$username = str_replace('.', '', $username);
			$username = preg_replace('/\+.*$/', '', $username);
		}

		// Set authenticated user
		$this->session['user'] = array(
			"email" => $username . '@' . $domain
		);

		// Redirect to the remembered destination
		if (isset($this->session['originalurl'])) {
			header('Location: '.$this->session['originalurl']);
			exit;
		} else {
			return $this->session['user'];
		}
	}


	private function determineEndPoints() {
		$request = new \HTTP\HTTPRequest(self::DISCOVERY_URL);
		$response = $request->send();
		$data = @json_decode($response->getBody(), true);
		if (empty($data['authorization_endpoint'])) {
			throw new \Exception('Failed to retrieve authentication endpoint from Google');
		} else {
			$this->endpoints = $data;
		}
	}
}
