<?php

namespace Controllers;

class AuthCallbackController extends \Controllers\BaseController {

    public function get() {
		
		try {
			$this->app->auth->receiveCallback($_GET);
		} catch (Exception $e) {
			$_SESSION = array();
			session_destroy();
			$this->resp->setContent("<p>Sorry, login with Google account failed with the following reason:</p><p><strong>".$e->getMessage()."</strong></p><p>Login process has been cancelled.  Want to <a href='http://edgeconf.com'>try again</a>?</p>");
		}

    }
}
