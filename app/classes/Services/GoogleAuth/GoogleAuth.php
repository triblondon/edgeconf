<?php

namespace Services\GoogleAuth;

class GoogleAuth {

	const INITIALURL = "https://www.google.com/accounts/o8/id";
	private $endpoint, $session, $options;
	private $defaults = array(
		'canceldest' => '/',
		'callback' => '/auth/callback'
	);

	function __construct(&$sessionhandler, $options = array()) {
		$this->session =& $sessionhandler;
		$this->options = array_merge($this->defaults, $options);
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

	function getAuthRedirectUrl() {
		$this->determineEndPoint();
		$associationdata = $this->associate();
		$this->session[$associationdata["assoc_handle"]] = $associationdata;
		$this->session["originalurl"] = $_SERVER['REQUEST_URI'];

		$protocol = isset($_SERVER["HTTPS"]) ? (($_SERVER["HTTPS"] === "on" or $_SERVER["HTTPS"] === 1 or $_SERVER["SERVER_PORT"] === 443) ? "https://" : "http://") : (($_SERVER["SERVER_PORT"] === 443) ? "https://" : "http://");
		$url = $this->endpoint."?".http_build_query(array(
			"openid.mode"=>"checkid_setup",
			"openid.ns"=>"http://specs.openid.net/auth/2.0",
			"openid.return_to"=>$protocol.$_SERVER["HTTP_HOST"].$this->options['callback'],
			"openid.claimed_id"=>"http://specs.openid.net/auth/2.0/identifier_select",
			"openid.identity"=>"http://specs.openid.net/auth/2.0/identifier_select",
			"openid.assoc_handle"=>$associationdata['assoc_handle'],
			"openid.ns.ax"=>"http://openid.net/srv/ax/1.0",
			"openid.ax.mode"=>"fetch_request",
			"openid.ax.required"=>"email",
			"openid.ax.type.email"=>"http://axschema.org/contact/email"
		));
		return $url;
	}

	function receiveCallback($params) {

		// React appropriately to cancellations
		if (isset($params["openid_mode"]) and $params["openid_mode"] == "cancel") {
			if (isset($this->session['originalurl'])) {
				header('Location: '.$this->session['originalurl']);
			} else {
				header("Location: ".$this->options['canceldest']);
			}
			exit;
		}

		// Check input contains basic parameters needed to check the signature
		$requiredparameters = array("openid_signed", "openid_sig", "openid_op_endpoint", "openid_claimed_id", "openid_assoc_handle", "openid_response_nonce");
		$notfound = array_diff($requiredparameters, array_keys(array_filter($_GET)));
		if (!empty($notfound)) {
			throw new \Exception("Missing:".join(", ", str_replace("openid_", "", $notfound)));
		}

		// Validate nonce
		$datepat = "/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z?/";
		if (!preg_match($datepat, $params["openid_response_nonce"], $m)) {
			throw new \Exception('Invalid nonce');
		}
		$noncetime = new \DateTime($m[0]);
		$now = new \DateTime;
		$nonceage = ($now->getTimestamp() - $noncetime->getTimestamp());
		$maxnonceage = 300;
		if ($nonceage > $maxnonceage) {
			throw new \Exception('Expired nonce');
		}

		// Check against previously stored signing key, and then delete the key to prevent replays
		if (empty($this->session[$params["openid_assoc_handle"]])) {
			throw new \Exception('No pending authentication request found');
		} else {
			$assocdata = $this->session[$params["openid_assoc_handle"]];
			unset($this->session[$params["openid_assoc_handle"]]);
		}

		// Check signature
		$tosign = "";
		foreach (explode(",", $params["openid_signed"]) as $field) {
			$tosign .= $field.":".$params["openid_".str_replace(".", "_", $field)]."\n";
		}
		$generatedsignature = base64_encode(hash_hmac("sha256", $tosign, base64_decode($assocdata["mac_key"]), true));
		if ($generatedsignature != $params["openid_sig"]) {
			throw new \Exception('Incorrect signature');
		}

		// Get the email address
		list($username, $domain) = explode('@', $_GET["openid_ext1_value_email"]);

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


	private function determineEndPoint() {
		$responsebody = self::makeHTTPRequest(self::INITIALURL);
		$doc = new \DOMDocument();
		@$doc->loadXML($responsebody);
		$nodes = $doc->getElementsByTagName("URI");
		if (empty($nodes->item(0)->nodeValue)) {
			throw new \Exception('Failed to retrieve authentication endpoint from Google');
		} else {
			$this->endpoint = $nodes->item(0)->nodeValue;
		}
	}

	private function associate() {
		$responsebody = self::makeHTTPRequest($this->endpoint."?".http_build_query(array(
			"openid.mode" => "associate",
			"openid.ns" => "http://specs.openid.net/auth/2.0",
			"openid.return_to" => "http://edgeconf.com",
			"openid.claimed_id" => "http://specs.openid.net/auth/2.0/identifier_select",
			"openid.identity" => "http://specs.openid.net/auth/2.0/identifier_select",
			"openid.assoc_type" => "HMAC-SHA256",
			"openid.session_type" => "no-encryption",
		)));

		if (empty($responsebody)) {
			throw new \Exception('Association failed');
		}

		$foundfields = array();
		foreach (preg_split("/[\n\r]+/", $responsebody) as $line) {
			if (preg_match("/^([^:]+):(.*)/", $line, $m) and !empty($m[2])) {
				$foundfields[$m[1]] = $m[2];
			}
		}

		$requiredfields = array("ns", "session_type", "assoc_type", "assoc_handle", "expires_in", "mac_key");
		$notfound = array_diff($requiredfields, array_keys($foundfields));
		if (!empty($notfound)) {
			throw new \Exception("Missing fields in associate response");
		}

		return $foundfields;
	}



	private static function makeHTTPRequest($url) {
		$request = new \HTTP\HTTPRequest($url);
		$response = $request->send();
		$responsebody = $response->getBody();

		return $responsebody;
	}
}
