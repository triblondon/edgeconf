<?php

namespace Routing;

class Request {

	// Raw HTTP data
	private $scheme, $method, $host, $path, $query, $headers, $body;

	// Parsed data
	private $post, $cookies, $files;

	public function __construct($scheme, $method, $host, $path, $query, $headers, $body, $post, $cookies, $files) {
		$this->scheme = $scheme;
		$this->method = $method;
		$this->host = $host;
		$this->path = $path;
		$this->query = $query;
		$this->headers = $headers;
		$this->body = $body;
		$this->post = $post;
		$this->cookies = $cookies;
		$this->files = $files;
	}

	public function getQuery($key=null) {
		return $key ? isset($this->query[$key]) ? $this->query[$key] : null : $this->query;
	}

	public function getPost($key=null) {
		return $key ? isset($this->post[$key]) ? $this->post[$key] : null : $this->post;
	}

	public function getPath() {
		return $this->path;
	}

	public function getMethod() {
		return $this->method;
	}

	/**
	 * Creates a new request with values from PHP's super globals.
	 *
	 * @return Request A new request
	 */
	public static function createFromGlobals() {

		if (empty($_SERVER['HTTP_HOST'])) throw new Exception("Not in HTTP context");

		$scheme = empty($_SERVER['HTTPS']) ? 'http' : 'https';
		$method = $_SERVER['REQUEST_METHOD'];
		$host = $_SERVER['HTTP_HOST'];
		$path = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
		$headers = array();
		foreach ($_SERVER as $name => $value) {
			if (substr($name, 0, 5) == 'HTTP_') {
				$headers[str_replace('_', '-', strtolower(substr($name, 5)))] = $value;
			}
		}
		$body = file_get_contents("php://input");

		return new self($scheme, $method, $host, $path, $_GET, $headers, $body, $_POST, $_COOKIE, $_FILES);
	}

}
