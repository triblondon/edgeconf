<?php
/**
 * Access the response to an HTTP Request made with the HTTPRequest class
 *
 * @codingstandard Assanka
 * @copyright Assanka Limited [All rights reserved]
 * @author Andrew Betts <andrew.betts@assanka.net>
*/

namespace HTTP;

class HTTPResponse {
	private $headers;
	private $body;
	private $cookies;
	private $responsetext;
	private $reqtime;
	private $statuscode;

	/**
	 * Create an HTTPResponse
	 *
	 * Creates and HTTPResponse with data from the request session.  Normally created and populated by the HTTPRequest class
	 *
	 * @param array       $headers      Key / Value array containing the response headers (excluding any Set-cookie headers)
	 * @param array       $cookies      Key / Value array containing the response cookies
	 * @param string      $body         Text of the response body
	 * @param string      $responsetext Full text of the HTTP response
	 * @param int         $statuscode   HTTP status code of the response (e.g. 200 or 404)
	 * @param float       $time         Time taken to complete the HTTP request/response, in seconds
	 * @param HTTPRequest $req          The HTTPRequest that created this response (deprecated)
	 * @return HTTPResponse
	*/
	public function __construct($headers, $cookies, $body, $responsetext, $statuscode, $time, $req = false) {
		$this->headers = $headers;
		$this->body = $body;
		$this->cookies = $cookies;
		$this->responsetext = $responsetext;
		$this->statuscode = $statuscode;
		$this->reqtime = $time;
	}

	/**
	 * Returns all headers
	 *
	 * @return array
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Returns the value of a specified header
	 *
	 * @param string $key the key to a corresponding header
	 * @return String the header value
	 */
	public function getHeader($key) {
		$key = strtolower($key);
		return (empty($this->headers[$key])) ? false : $this->headers[$key];
	}

	/**
	 * Returns all cookies set in the response
	 *
	 * @return array
	 */
	public function getCookies() {
		return $this->cookies;
	}

	/**
	 * Returns the body of the response.
	 *
	 * @return String The body of the return (no headers)
	*/
	public function getBody() {
		return $this->body;
	}

	/**
	 * Returns the body of the response, parsed according to the content-type header given by the server.
	 *
	 * Content types supported are:
	 *
	 * application/x-www-form-urlencoded
	 * application/php
	 * application/json
	 *
	 * If the content type is not supported or not specified, the raw content body is returned.
	 *
	 * @param string $type Forces the body to be interpreted as the specified type (choose from 'json', 'php', 'urlenc')
	 * @return mixed A structured representation of the data received in the response body
	*/
	public function getData($type=false) {
		if (empty($this->headers['content-type']) and empty($type)) return $this->body;
		if (empty($type)) $type = $this->headers['content-type'];
		$type = preg_replace("/;\s*charset\s*=\s*.*$/i", "", $type);
		$data = array();
		switch ($type) {
			case 'application/x-www-form-urlencoded':
			case 'urlenc':
				$resultparams = explode('&', $this->body);
				foreach ($resultparams as $param) {
					if (strpos($param, '=') === false) {
						$data[] = urldecode($param);
					} else {
						list($key, $val) = explode('=', $param, 2);
						if (strpos($key, '[]') == (strlen($key) - 2)) {
							$key = rtrim($key, '[]');
							if (empty($data[$key]) or !is_array($data[$key])) $data[$key] = array();
							$data[$key][] = urldecode($val);
						} else {
							$data[$key] = urldecode($val);
						}
					}
				}
				break;
			case 'application/php':
			case 'php':
				$data = unserialize($this->body);
				break;
			case 'application/json':
			case 'json':
				$data = json_decode($this->body, true);
				break;
			case 'application/atom+xml':
			case 'text/xml':
			case 'xml':
				require_once $_SERVER['CORE_PATH']."/helpers/common/v2/common";
				$data = Common::xml2array($this->body);
				break;
			case 'xmlrpc':
				$data = xmlrpc_decode($this->body);
				break;
			default:
				$data = $this->body;
		}
		return $data;
	}

	/**
	 * Returns the whole of the response.
	 *
	 * @return String whole of the response (body and all headers)
	*/
	public function getResponse() {
		return $this->responsetext;
	}

	/**
	 * Returns the total time required for the request and response
	 *
	 * @return float Time in seconds
	*/
	public function getResponseTime() {
		return $this->reqtime;
	}

	/**
	 * Returns the response code given by the server.
	 *
	 * @return Integer HTTP response status code
	*/
	public function getResponseStatusCode() {
		return $this->statuscode;

	}

	/**
	 * Returns the W3C response status description text for the response status returned by the server.
	 *
	 * Note that if the server returned a non-standard response status description, this is ignored.
	 *
	 * @return Integer HTTP response status code
	*/
	public function getResponseStatusDesc() {
		$desc = array("100"=>"Continue", "101"=>"Switching Protocols", "200"=>"OK", "201"=>"Created", "202"=>"Accepted", "203"=>"Non-Authoritative Information", "204"=>"No Content", "205"=>"Reset Content", "206"=>"Partial Content", "300"=>"Multiple Choices", "301"=>"Moved Permanently", "302"=>"Found", "303"=>"See Other", "304"=>"Not Modified", "305"=>"Use Proxy", "307"=>"Temporary Redirect", "400"=>"Bad Request", "401"=>"Unauthorized", "402"=>"Payment Required", "403"=>"Forbidden", "404"=>"Not Found", "405"=>"Method Not Allowed", "406"=>"Not Acceptable", "407"=>"Proxy Authentication Required", "408"=>"Request Timeout", "409"=>"Conflict", "410"=>"Gone", "411"=>"Length Required", "412"=>"Precondition Failed", "413"=>"Request Entity Too Large", "414"=>"Request-URI Too Long", "415"=>"Unsupported Media Type", "416"=>"Requested Range Not Satisfiable", "417"=>"Expectation Failed", "500"=>"Internal Server Error", "501"=>"Not Implemented", "502"=>"Bad Gateway", "503"=>"Service Unavailable", "504"=>"Gateway Timeout", "505"=>"HTTP Version Not Supported");
		return isset($desc[$this->statuscode]) ? $desc[$this->statuscode] : null;

	}
}
