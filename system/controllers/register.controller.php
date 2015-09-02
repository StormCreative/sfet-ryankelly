<?php
/*
# REGISTER CONTROLLER
# by Danny Broadbent
*/
class RegisterController extends BaseController {
	public function __construct() {
		parent::__construct();
	}

	public function view() {
		$this->setTitle('Register');
		$this->addScript('require.min', 'development/form');
		$this->addData('progress', 1);
	}

	public function process() {
		$this->unsetHeaderFooter();
		$this->setRenderView(false);

		$register = $this->model('users');
		$login = $this->model('users');
		$registerChk = clone $register;
		parse_str($this->_post['fields'], $field);
		parse_str($this->_post['required_fields'], $required);
		$this->EmptyFields($required);

		$field['salt'] = $register->GetRandomString(10);
		$field['email'] = $field['username'];
		$field['password'] = sha1(PASSWORD_SALT.$field['salt'].$field['password']);

		if (is_array($this->empty_fields) && count($this->empty_fields)==0) {
			$this->SetEmail($field['username']);
			if ($this->ValidateEmail()) {
				$registerChk->username($field['username']);
				if (!$registerChk->LoadRecords()) {
					unset($field['confirm_password']);

					foreach ($field as $column => $value) {
						$register->SetField($column, $value);
					}
					$register->SaveRecord();

					$login = $this->model('users');
					$login->username($field['username']);
					if ($login->LoadRecords()) {
						while ($login->Read()) {
							if ($login->f('password') == $field['password']) {
								$session = sha1(time().$login->f('password').'LOGGEDIN'.mt_rand(5, 15));
								setcookie('userId', $login->f('id'), time()+3600*60*60*60, '/', ($this->ENV=='LIVE'?'.':'').str_replace('www.', '', str_replace(':'.$this->_server['SERVER_PORT'], '', $this->site['default_domain'])), false, false);
								setcookie('username', $login->f('username'), time()+3600*60*60*60, '/', ($this->ENV=='LIVE'?'.':'').str_replace('www.', '', str_replace(':'.$this->_server['SERVER_PORT'], '', $this->site['default_domain'])), false, false);
								setcookie('activeLogin', $session, time()+3600*60*60*60, '/', ($this->ENV=='LIVE'?'.':'').str_replace('www.', '', str_replace(':'.$this->_server['SERVER_PORT'], '', $this->site['default_domain'])), false, false);
								$_SESSION['userId'] = $login->f('id');
								$_SESSION['username'] = $login->f('username');
								$_SESSION['activeLogin'] = $session;
								$updateAccount = $this->model('users');
								$updateAccount->SetField('id', $login->f('id'));
								$updateAccount->SetField('session', $session);
								$updateAccount->SetField('last_login', date('Y-m-d H:i:s'));
								$updateAccount->SaveRecord();
							}
						}
					}

					$sendEmail = $this->helper('email');
					$sendEmail->setTo($field['email'], $field['forename'].' '.$field['surname']);
					$sendEmail->setFrom($this->site['email_noreply'], $this->site['name']);
					$sendEmail->setSubject('Welcome to '.$this->site['name']);
					foreach ($field as $name => $value) {
						$sendEmail->addData($name, $value);
					}
					$sendEmail->setTemplate('client/welcome');
					$sendEmail->email();

					$sendAdminEmail = $this->helper('email');
					$sendAdminEmail->setTo($this->site['email'], $this->site['name']);
					$sendAdminEmail->setFrom($this->site['email_noreply'], $this->site['name']);
					$sendAdminEmail->setSubject('Welcome to '.$this->site['name']);
					foreach ($field as $name => $value) {
						$sendAdminEmail->addData($name, $value);
					}
					$sendAdminEmail->setTemplate('admin/new_client');
					$sendAdminEmail->email();

					if ($register->LastInsertID()) {
						$result = array(
							'status' => 'success',
							'redirect' => $this->main_url.'/my-account/welcome'
						);
					} else $result = array(
						'status' => 'error',
						'msg' => 'An error occurred whilst trying to save your details, please try again later!'
					);
				} else $result = array(
					'status' => 'error',
					'fields' => 'username',
					'msg' => 'An account with this email address already exists'
				);
			} else $result = array(
				'status' => 'error',
				'fields' => 'username',
				'msg' => 'Please enter a *valid* email address'
			);
		} else $result = array(
			'status' => 'error',
			'fields' => $this->empty_fields
		);

		echo json_encode($result);
		exit;
	}
}