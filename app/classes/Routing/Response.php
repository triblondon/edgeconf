<?php

namespace Routing;

class Response {

	private $_status = 200;
	private $_cachettl = 0;
	private $_headers = array();
	private $_content;

	private $_statusdesc = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Time-out',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Large',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Time-out',
		505 => 'HTTP Version not supported',
	);

	/**
	 * Set a header on the response
	 *
	 * @param string $key   The header Key
	 * @param string $value Value
	 * @return void
	 */
	public function setHeader($key, $value) {
		$this->_headers[$key] = $value;
	}

	public function setStatus($code) {
		$this->_status = $code;
	}

	/**
	 * Sets the response content.
	 *
	 * @param mixed $content content
	 * @return void
	 */
	public function setContent($content) {
		$this->_content = $content;
	}


	/* Convenience methods to set combinations of response properties for common use cases */

	public function redirect($to, $type=303) {
		$this->setStatus($type);
		$this->setHeader('Location', $to);
		$this->setContent('');
	}

	public function setCacheTTL($ttl, $isprivate=false) {
		$tokens = array();
		if (is_numeric($ttl) and $ttl > 0) {
			$tokens[] = 'max-age='.$ttl;
			$tokens[] = $isprivate ? 'private' : 'public';
		} else if (!$ttl) {
			$tokens[] = 'no-store';
		}
		$this->setHeader('Cache-Control', join(', ', $tokens));
	}

	public function setJSON($content) {
		$this->setHeader('Content-Type', 'application/json; charset=UTF-8');

		// TODO:MA:20130710 Fire an exception if data is not json-ifiable.
		// Look up PHP docs for json_last_error
		$this->setContent(json_encode($content));
	}


	/**
	 * Get all the headers on the response
	 *
	 * @return array
	 */
	public function getHeaders() {
		return $this->_headers;
	}

	/**
	 * Gets the current response content.
	 *
	 * @return mixed
	 */
	public function getContent() {
		return $this->_content;
	}

	/**
	 * Sends HTTP headers.
	 *
	 * @return void
	 */
	public function sendHeaders() {
		header('HTTP/1.1 '.$this->_status.' '.$this->_statusdesc[$this->_status]);
		foreach ($this->_headers as $id => $each) {
			header("$id: $each");
		}
	}

	/**
	 * Send content
	 *
	 * @return string
	 */
	public function sendContent() {
		echo $this->_content;
	}

	/**
	 * Sends HTTP headers and content.
	 *
	 * @return string
	 */
	public function serve() {
		$this->sendHeaders();
		$this->sendContent();
	}
}
