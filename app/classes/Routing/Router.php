<?php

namespace Routing;

final class Router {

	protected $routes = array(), $patterns = array(), $errorcontrollers = array(), $controllerns = 'Controllers';
	protected $di;

	public function __construct($di) {
		$this->di = $di;
	}

	public function setPattern($name, $pattern) {
		$this->patterns[$name] = $pattern;
	}

	public function route($pattern, $dest) {
		$this->routes[$pattern] = $dest;
	}

	public function errorUnsupportedMethod($dest) {
		$this->errorcontrollers['unsupportedmethod'] = $dest;
	}

	public function errorNoRoute($dest) {
		$this->errorcontrollers['noroute'] = $dest;
	}

	public function dispatch($req, $resp) {

		$path = '/'.trim($req->getPath(), '/');
		$method = strtolower($req->getMethod());
		$patterns = $this->patterns;
		$controllerclass = null;
		foreach ($this->routes as $pattern => $dest) {
			$pattern = preg_replace_callback('/\:(\w+)/', function($m) use ($patterns) {
				return isset($patterns[$m[1]]) ? '(?<'.$m[1].'>'.$patterns[$m[1]].')' : $m[0];
			}, $pattern);
			$pattern = '~^'.str_replace('~', '\~', $pattern).'$~i';
			if (preg_match($pattern, $path, $m)) {
				if (substr($dest, 0, 1) == '/') {
					$dest = preg_replace_callback('/\{\{(\w+)\}\}/', function($m_dest) use($m) {
						return isset($m[$m_dest[0]]) ? $m[$m_dest[0]] : $m[0];
					}, $dest);
					$resp->setStatus(301);
					$resp->setHeader('Location', $dest);

				} else {
					$controllerclass = $this->controllerClass($dest);
					if (!class_exists($controllerclass) or (!method_exists($controllerclass, $method) and !method_exists($controllerclass, 'all'))) {
						if (isset($this->errorcontrollers['unsupportedmethod'])) {
							$controllerclass = $this->controllerClass($this->errorcontrollers['unsupportedmethod']);
							$urlargs = array('allowedmethods'=>array('TODO'));
						} else {
							throw new \Exception('Method not allowed');
						}
					} else {
						foreach ($m as $key => $value) if (is_int($key)) unset($m[$key]);
						$urlargs = $m;
					}
				}
				break;
			}
		}

		if (!$controllerclass) {
			$controllerclass = $this->getNoRouteControllerClass();
			$urlargs = array();
		}
		
		$controller = new $controllerclass($this->di, $req, $resp, $urlargs);
		$initresult = $controller->initialise();
		if ($initresult === false) {
			$controllerclass = $this->getNoRouteControllerClass();
			$controller = new $controllerclass($this->di, $req, $resp, array());
		} else if ($initresult !== true) {
			if (!method_exists($controller, $method)) $method = 'all';
			$controller->$method();
		}
	}

	private function controllerClass($dest) {
		return '\\'.$this->controllerns.'\\'.$dest.'Controller';
	}
		
	private function getNoRouteControllerClass() {
		if (isset($this->errorcontrollers['noroute'])) {
			return $this->controllerClass($this->errorcontrollers['noroute']);
		} else {
			throw new \Exception('No matching route found');
		}
	}
}
