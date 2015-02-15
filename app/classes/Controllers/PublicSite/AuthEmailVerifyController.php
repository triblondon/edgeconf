<?php

namespace Controllers\PublicSite;

class AuthEmailVerifyController extends \Controllers\PublicSite\PublicBaseController {

	public function post() {
		$this->authenticate();
		$this->resp->setStatus($this->user ? 200 : 403);
    }
}
