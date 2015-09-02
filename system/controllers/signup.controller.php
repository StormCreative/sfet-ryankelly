<?php
/*
# SIGNUP CONTROLLER
# by Ryan Kelly
*/
class SignupController extends BaseController {
	public function __construct() {
		parent::__construct();
	}

	public function process() {
		$this->unsetHeaderFooter();
		$this->setRenderView(false);
        
        $signup = $this->model('users_1');
        $signup->SetField('name', $this->_post['name']);
        $signup->SetField('email', $this->_post['email']);
        if($signup->SaveRecord()){
            header('Location: /success');
        } else {
            header('Location: /failure');
        }
        
    }
}