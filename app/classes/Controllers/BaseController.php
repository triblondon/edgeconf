<?php

namespace Controllers;

abstract class BaseController {

	protected $app, $req, $resp, $routeargs;
	protected $viewdata = array();

	final public function __construct($di, $req, $resp, $routeargs) {
		$this->app = $di;
		$this->req = $req;
		$this->resp = $resp;
		$this->routeargs = $routeargs;

		if (!session_id()) session_start();
	}

	public function initialise() { }


	protected function addViewData($a, $val=null) {
		if (is_scalar($a) and $val !== null) {
			$this->viewdata[$a] = $val;
		} else if (is_array($a) and $val === null) {
			$this->viewdata = array_merge($this->viewdata, $a);
		} else {
			throw new \Exception('Invalid function overloading');
		}
	}

	protected function renderView($templ) {
		if (!empty($_SESSION['alerts'])) {
			$this->viewdata['alerts'] = $_SESSION['alerts'];
			unset($_SESSION['alerts']);
		}
		$this->resp->setContent(
			$this->app->view->render($templ.'.html', $this->viewdata)
		);
	}

	protected function alert($type, $content) {
		if (empty($_SESSION['alerts'][$type])) $_SESSION['alerts'][$type] = array();
		$_SESSION['alerts'][$type][] = $content;
	}
}
