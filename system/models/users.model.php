<?php
/*
# USERS MODEL
# by Danny Broadbent
*/
class UsersModel extends DatabaseModel {
	private $model = 'users';

	public function __construct() {
		parent::__construct();

		// define table name and primary key
		$this->SetTableName($this->model);
		$this->AutoFields();

		// define default order fields
		unset($this->defaultOrderFields['id']);
		$this->defaultOrderFields['`'.$this->model.'`.`username`'] = 'ASC';
	}

	public function username($username) {
		$this->AddWhere('AND (`'.$this->model.'`.`username` LIKE "'.$this->escape_string((string)$username).'" OR `'.$this->model.'`.`email` LIKE "'.$this->escape_string((string)$username).'")');
	}

	public function fsession($fsession) {
		$this->AddWhere('AND `'.$this->model.'`.`fsession` LIKE "'.$fsession.'"');
	}

	public function password($password, $salt='') {
		$this->AddWhere('AND `'.$this->model.'`.`password` LIKE "'.sha1(PASSWORD_SALT.(string)$salt.(string)$password).'"');
	}

	public function groupId($id) {
		if ($this->LoadRecord((int)$id)) {
			return $this->f('group_id');
		}
	}

	public function ApprovalAwaiting() {
		$this->AddWhere('AND approved = 0');
	}

	public function openSession($session) {
		$this->AddWhere('AND `'.$this->model.'`.`session` = "'.$session.'"');
		if ($this->LoadRecords()) {
			while ($this->read()) {
				setcookie('userId', $this->f('id'), time()+3600*60*60*60, '/', '.'.str_replace('www.', '', SYS_DOMAIN));
				setcookie('username', $this->f('username'), time()+3600*60*60*60, '/', '.'.str_replace('www.', '', SYS_DOMAIN));
				setcookie('activeLogin', $session, time()+3600*60*60*60, '/', '.'.str_replace('www.', '', SYS_DOMAIN));
			}
		}
		$this->FreeResult();
	}

	public function sessionChk($userId, $username, $session) {
		$this->AddWhere('AND `'.$this->model.'`.`id` = '.$userId);
		$this->AddWhere('AND LOWER(`'.$this->model.'`.`username`) = "'.strtolower($username).'"');
		$this->AddWhere('AND `'.$this->model.'`.`session` = "'.$session.'"');
	}
}