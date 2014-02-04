<?php

namespace Controllers\Errors;

class Error404Controller extends \Controllers\BaseController {

    public function all() {
    	$this->resp->setStatus(404);
    	$this->resp->setContent("Sorry, ".$this->req->getPath()." was not found on this server.\n");
    }

}
