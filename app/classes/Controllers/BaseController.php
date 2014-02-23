<?php

namespace Controllers;

abstract class BaseController {

	protected $app, $req, $resp, $routeargs;
	protected $viewdata = array();

	final public function __construct($di, $req, $resp, $routeargs=array()) {
		$this->app = $di;
		$this->req = $req;
		$this->resp = $resp;
		$this->routeargs = $routeargs;
	}

	final public function dispatch($method) {
		if (!session_id()) session_start();
		if (method_exists($this, 'initialise')) {
			if ($this->initialise() === false) return;
		}
		if (method_exists($this, $method)) {
			$this->$method();
		} elseif (method_exists($this, 'all')) {
			$this->all();
		}
	}




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
		$this->viewdata['SERVER'] = $_SERVER;

		$this->resp->setContent(
			$this->app->view->render($templ.'.html', $this->viewdata)
		);
	}

	protected function alert($type, $content) {
		if (empty($_SESSION['alerts'][$type])) $_SESSION['alerts'][$type] = array();
		$_SESSION['alerts'][$type][] = $content;
	}




	final public static function getSupportedMethods() {
		return get_class_methods(get_called_class());
	}
}
