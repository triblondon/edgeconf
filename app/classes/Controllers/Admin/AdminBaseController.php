<?php

namespace Controllers\Admin;

class AdminBaseController extends \Controllers\BaseController {

	protected $user;
	private $whitelist = array(
		'andrewbetts@gmail.com',
		'tim.davenport@ft.com',
		'petele@google.com',
		'matthew.andrews@ft.com',
		'wilson.page@ft.com',
		'george.crawford@ft.com',
		'ada.edwards@ft.com',
		'rowan.beentje@ft.com',
		'torbenfoerster@gmail.com'
	);

	public function initialise() {
		$user = $this->app->auth->authenticate();
		if (!in_array(strtolower($user['email']), $this->whitelist)) {
			$this->resp->setStatus(403);
			$this->resp->setContent('Your Google account ('.$user['email'].') is not allowed access to this page.');
			return true;
		} else {
			$this->user = $user;
			$this->resp->setCacheTTL(0);
		}
	}

}
