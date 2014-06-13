<?php

namespace Controllers;

class AuthEmailSendCodeController extends \Controllers\BaseController {

    public function post() {

    	$code = substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890'), 0, 9);

		@session_start();
		if (empty($_SESSION['emailverify'])) $_SESSION['emailverify'] = array();
		$_SESSION['emailverify'][$this->req->getPost('email')] = $code;

    	$body = "Your code is: $code

What?
=====

As part of the registration process on edgeconf.com (the website for the Edge web conference) we ask people to verify that they own the email address they are using to register.  Someone is asserting that they own ".$this->req->getPost('email').", so this email is intended to allow them to prove that they can receive mail sent to that address.  If you didn't register for Edge, you can ignore this email.";

		mail($this->req->getPost('email'), 'Verify email for edgeconf.com', $body, "From: Edge <hello@edgeconf.com>");

    }
}
