<?php

namespace Controllers\PublicSite;

class AuthEmailVerifyController extends \Controllers\PublicSite\PublicBaseController {

    public function post() {
    	$this->authenticate();
		$this->resp->setStatus($this->person ? 200 : 403);
    }
}
