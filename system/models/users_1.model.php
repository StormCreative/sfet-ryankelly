<?php
/*
# USERS MODEL
# by Danny Broadbent
*/
class Users1Model extends DatabaseModel {
	private $model = 'users_1';

	public function __construct() {
		parent::__construct();

		// define table name and primary key
		$this->SetTableName($this->model);
		$this->AutoFields();

	}

	
}