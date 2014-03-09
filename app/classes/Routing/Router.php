<?php
/**
 * Request router
 *
 * Dispatches the appropriate controllers to handle specified URL routes
 */

namespace Routing;

final class Router {

	private $options = array(

		// PHP class namespace in which controllers can be found
		"controllerns" => "Controllers",

		// Strings to prefix and suffix to the controller name to get the controller class name
		"controllerprefix" => "",
		"controllersuffix" => "Controller",

		// Whether to match request URI case sensitively
		"matchcase" => false,

		// Behaviour when request URI ends in a trailing slash.  `redirect` = strip and redirect, `ignore` = strip before matching, `exact` = include in match (a path that ends in a slash may be treated differently to one that doesn't).
		"dirmatch" => "redirect",

		// HTTP methods allowed
		"validmethods" => array("options", "get", "head", "post", "put", "delete", "trace", "connect"),
	);
	private $di;
	private $routes = array(), $patterns = array(), $errorcontrollers = array();


	/**
	 * Create a router
	 *
	 * @param object $di      Dependency injection container to pass to any controllers instantiated by the router
	 * @param array  $options Array of config key-value data for the router instance.
	 * @return Router
	 */
	public function __construct($di, $options = null) {
		$this->di = $di;
		if (is_array($options)) $this->options = array_merge($this->options, $options);
	}

	/**
	 * Set a common URL slug pattern
	 *
	 * If the router makes use of the same slug pattern in more than one route, creating a common pattern allows the route itself to refer to the slug using :slugname, eg. `$myrouter->route('/:eventslug/video', '...');
	 *
	 * @param string $name    Name of slug (take care to choose a name that will not occur in the rest of the URL)
	 * @param string $pattern PREG regex, without delimiters
	 * @return void
	 */
	public function setPattern($name, $pattern) {
		$this->patterns[$name] = $pattern;
	}

	/**
	 * Configure a route
	 *
	 * @param string $pattern PREG regular expression, without delimiters
	 * @param string $dest    Name of class to invoke (may include namespace path), or a new request URI to redirect to
	 * @return void
	 */
	public function route($pattern, $dest) {
		$this->routes[$pattern] = $dest;
	}

	/**
	 * Set a default route for unsupported method requests
	 *
	 * If a request matches a configured route but the controller for that route rejects the request's HTTP verb, the specified controller will be invoked instead.
	 *
	 * @param string $dest Name of class to invoke.  May include namespace path.
	 * @return void
	 */
	public function errorUnsupportedMethod($dest) {
		$this->errorcontrollers['unsupportedmethod'] = $dest;
	}

	/**
	 * Set a default route for requests that do not match a configured route
	 *
	 * @param string $dest Name of class to invoke.  May include namespace path.
	 * @return void
	 */
	public function errorNoRoute($dest) {
		$this->errorcontrollers['noroute'] = $dest;
	}

	/**
	 * Dispatch the router
	 *
	 * @param Request  $req  Representation the HTTP request to process
	 * @param Response $resp Response object to populate with the controller's output
	 * @return void
	 */
	public function dispatch(Request $req, Response $resp) {

		if ($this->options['dirmatch'] == 'ignore') {
			$path = '/'.trim($req->getPath(), '/');
		} elseif ($this->options['dirmatch'] == 'redirect' and substr($req->getPath(), -1, 1) === '/' and $req->getPath() !== '/') {
			$resp->setStatus(301);
			$resp->setHeader('Location', rtrim($req->getPath(), '/'));
			return;
		} else {
			$path = $req->getPath();
		}
		$method = strtolower($req->getMethod());
		$patterns = $this->patterns;
		$controller = null;
		$allowedmethods = array();
		foreach ($this->routes as $pattern => $dest) {
			$pattern = preg_replace_callback('/\:(\w+)/', function($m) use ($patterns) {
				return isset($patterns[$m[1]]) ? '(?<'.$m[1].'>'.$patterns[$m[1]].')' : $m[0];
			}, $pattern);
			$pattern = '~^'.str_replace('~', '\~', $pattern).'$~'.($this->options['matchcase']?'':'i');
			if (preg_match($pattern, $path, $m)) {

				// Handle naive redirects
				if (substr($dest, 0, 1) == '/') {
					$dest = preg_replace_callback('/\{\{(\w+)\}\}/', function($m_dest) use($m) {
						return isset($m[$m_dest[0]]) ? $m[$m_dest[0]] : $m[0];
					}, $dest);
					$resp->setStatus(301);
					$resp->setHeader('Location', $dest);
					return;
				}

				// Find applicable controller class
				if (!$controllerclass = $this->controllerClass($dest)) continue;

				// If controller does not support the request method, record the methods it does support (so if no controller is found that can handle the request, an unsupported method error can be returned), then proceed to next route
				$availablemethods = array_intersect($controllerclass::getSupportedMethods(), $this->options['validmethods']);
				if (!in_array($method, $availablemethods)) {
					$allowedmethods = array_merge($allowedmethods, $availablemethods);
					continue;
				}

				// Extract and index the URL slugs
				foreach ($m as $key => $value) if (is_int($key)) unset($m[$key]);
				$urlargs = $m;

				// Dispatch the controller.  If controller throws RouteRejectedException (either in contructor or verb method) treat the route as unmatched and attempt to find an alternative
				try {
					$controller = new $controllerclass($this->di, $req, $resp, $urlargs);
					if (!method_exists($controller, $method)) $method = 'all';
					$controller->dispatch($method);
				} catch (RouteRejectedException $e) {

					// TODO: Also consider resetting the $resp object to a blank state
					$controller = null;
					continue;
				}

				break;
			}
		}

		// If no controller was found to handle the request, assign a default one
		if (!$controller) {
			if ($allowedmethods) {
				$controllerclass = $this->getDefaultRouteControllerClass('unsupportedmethod');
				$urlargs = array('allowedmethods'=>$allowedmethods);
			} else {
				$controllerclass = $this->getDefaultRouteControllerClass('noroute');
				$urlargs = array();
			}
			$controller = new $controllerclass($this->di, $req, $resp, $urlargs);
			if (!method_exists($controller, $method)) $method = 'all';
			$controller->$method();
		}

	}




	private function controllerClass($dest) {
		$class = '\\' . $this->options['controllerns'] . '\\' . $this->options['controllerprefix'] . $dest . $this->options['controllersuffix'];
		return class_exists($class) ? $class : false;
	}

	private function getDefaultRouteControllerClass($type) {
		if (isset($this->errorcontrollers[$type])) {
			return $this->controllerClass($this->errorcontrollers[$type]);
		} else {
			throw new \Exception('No matching route found and no default '.$type.' controller is configured to handle this request');
		}
	}
}
