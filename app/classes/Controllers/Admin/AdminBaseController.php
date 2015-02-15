<?php

namespace Controllers\Admin;

class AdminBaseController extends \Controllers\BaseController {

	protected $user;

	public function initialise() {
		$user = $this->app->auth->authenticate();
		$validusers = preg_split('/\s*,\s*/', $this->app->config->auth->admins);
		if (!in_array(strtolower($user['email']), $validusers)) {
			$this->resp->setStatus(403);
			$this->addViewData('user', $user);
			$this->renderView('errors/403');
			return false;
		} else {
			$this->user = $user;
			$this->resp->setCacheTTL(0);
		}
	}

}
