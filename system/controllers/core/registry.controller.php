<?php
/*
# REGISTRY CONTROLLER
# by Danny Broadbent
*/
final class Registry {
	private $data = array();
	
	public function get($key = NULL) {
		if ($key != NULL)
			return (isset($this->data[$key]) ? $this->data[$key] : NULL);
		else
			return $this->data;
	}
	
	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	public function has($key) {
		return isset($this->data[$key]);
	}
}