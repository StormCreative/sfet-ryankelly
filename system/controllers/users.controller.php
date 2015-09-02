<?php
/*
# HOME CONTROLLER
# by Danny Broadbent
*/
class UsersController extends BaseController {
	private $user;
	
	public function __construct() {
		parent::__construct();
	}
	
	public function user() {
		$this->user = $this->model('users');
		if ($this->user->LoadRecord($this->__get('user_id'))) {
			$this->addData('forename', $this->user->f('forename'));
		}
		$this->setView('users');
	}
}