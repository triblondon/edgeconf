<?php

namespace Controllers;

class HubController extends \Controllers\BaseController {

	public function get() {
		$this->renderView('hub');
	}
}
