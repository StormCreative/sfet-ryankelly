<?php
/*
# USER GROUPS MODEL
# by Danny Broadbent

group_id - 1 = User, 10 = Admin

*/
class UserGroupsModel extends DatabaseModel {
	public function __construct() {
		parent::__construct();
		
		// define table name and primary key
		$this->SetTableName('user_groups');
		$this->AutoFields();
	}
}