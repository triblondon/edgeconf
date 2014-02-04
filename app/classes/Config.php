<?php

class Config {

	private $data = array();

	public function __construct($path) {
		if (is_array($path)) {
			$this->data = $path;
		} else {
			$this->data = parse_ini_file($path, true);
		}
	}

	public function __get($key) {
		if (!isset($this->data[$key])) return null;
		return is_array($this->data[$key]) ? new Config($this->data[$key]) : $this->data[$key];
	}
}
