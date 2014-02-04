<?php

namespace Controllers\Errors;

class Error405Controller extends \Controllers\BaseController {

    public function all() {
    	$this->resp->setStatus(405);
    	$this->resp->setContent("Sorry, ".$this->req->getMethod()." is not allowed on this resource.\n");

    	// TODO: Add an Allow header listing allowed methods
    }

}
