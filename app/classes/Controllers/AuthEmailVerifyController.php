<?php

namespace Controllers;

class AuthEmailVerifyController extends \Controllers\BaseController {

    public function post() {

    	@session_start();

		if (!empty($_SESSION['emailverify'][$this->req->getPost('email')]) and $_SESSION['emailverify'][$this->req->getPost('email')] == trim($this->req->getPost('code'))) {

			// Get the email address
			list($username, $domain) = explode('@', $this->req->getPost('email'));

			// Canonicalise GMail addresses: googlemail and gmail are the same,
			// dots in username are ignored, as is anything after a +
			if ($domain == 'googlemail.com') $domain = 'gmail.com';
			if ($domain == 'gmail.com') {
				$username = str_replace('.', '', $username);
				$username = preg_replace('/\+.*$/', '', $username);
			}

			// Inject a user into the session var used by GoogleAuth, so it thinks the user is already authenticated
			$_SESSION['user'] = array('email' => $username . '@' . $domain);

		} else {
			$this->resp->setStatus(403);
		}
    }
}
