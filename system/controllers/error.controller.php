<?php
/*
# ERROR CONTROLLER
# by Danny Broadbent
*/
class ErrorController extends BaseController {
	public function __construct() {
		parent::__construct();
		$this->cacheType('None');
	}

	public function view($err_code) {
		$this->setTitle('Error: '.$err_code);
		$this->addData('hide_nav', true);
		$this->addData('user', $this->user);
		$this->addData('is_admin', $this->isAdmin($this->user['id']));

		$this->setView('error/'.$err_code);
	}
}