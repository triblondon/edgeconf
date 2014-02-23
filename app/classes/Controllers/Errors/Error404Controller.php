<?php

namespace Controllers\Errors;

class Error404Controller extends \Controllers\BaseController {

	public function all() {
		$this->resp->setStatus(404);
		$this->renderView('errors/404');
	}

}
