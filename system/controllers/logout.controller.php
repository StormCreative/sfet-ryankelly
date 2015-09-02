<?php
/*
# LOGOUT CONTROLLER
# by Danny Broadbent
*/
class LogoutController extends BaseController {
	public function __construct() {
		parent::__construct();
	}

	public function view() {
		$this->setTitle('Logout');
		$this->addScript('require.min', 'development/form');

		setcookie('userId', '', time()-3600*60*60*60, '/', ($this->ENV=='LIVE'?'.':'').str_replace('www.', '', str_replace(':'.$this->_server['SERVER_PORT'], '', $this->site['default_domain'])), false, false);
		setcookie('username', '', time()-3600*60*60*60, '/', ($this->ENV=='LIVE'?'.':'').str_replace('www.', '', str_replace(':'.$this->_server['SERVER_PORT'], '', $this->site['default_domain'])), false, false);
		setcookie('activeLogin', '', time()-3600*60*60*60, '/', ($this->ENV=='LIVE'?'.':'').str_replace('www.', '', str_replace(':'.$this->_server['SERVER_PORT'], '', $this->site['default_domain'])), false, false);
		unset($_SESSION['userId']);
		unset($_SESSION['username']);
		unset($_SESSION['activeLogin']);

		$this->user = array('id'=>0);

		$this->redirect($this->main_url);
	}
}