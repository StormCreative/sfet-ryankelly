<?php
/*
# ERROR CONTROLLER
# by Danny Broadbent
*/
class ErrorController extends BaseController {
	public function __construct() {
		parent::__construct();
	}

	public function view($err_code) {
		$this->setView('error/'.$err_code);
	}
}