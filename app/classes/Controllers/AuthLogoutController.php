<?php

namespace Controllers;

class AuthLogoutController extends \Controllers\BaseController {

	public function get() {

		@session_start();
		$_SESSION = array();
		session_destroy();

		$url = isset($_GET['redir']) ? $_GET['redir'] : '/';
		$this->resp->redirect($url);
	}
}
