<?php
/*
# HELPER
# by Danny Broadbent
*/
class Helper extends SystemController {
	protected $data = array(); // PHP variables to pass through to template files
	
	public function __construct() {
		parent::__construct();
	}
	
	// Add PHP variables to be accessible via template
	public function addData($key, $val) {
		$this->data[$key] = $val;
	}
	
	// Get PHP variable by key or entire array if key not set
	public function getData($key = NULL) {
		if ($key != NULL)
			if (isset($this->data[$key]))
				return $this->data[$key];
			else
				return false;
		else
			return $this->data;
	}
	
	// Resets PHP variables for template
	public function unloadData() {
		$this->data = array();
	}
}