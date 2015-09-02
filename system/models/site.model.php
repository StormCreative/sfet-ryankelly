<?php
/*
# SITE MODEL
# by Danny Broadbent
*/
class SiteModel extends DatabaseModel {
	private $model = 'site';

	public function __construct() {
		parent::__construct();

		// define table name and primary key
		$this->SetTableName($this->model);
		$this->AutoFields();
	}

	public function FilterURL($url) {
		$hardChk = explode('.',str_replace('www.','',$url));
		if (count($hardChk)==4) {
			$hardChk = $hardChk[count($hardChk)-3].'.'.$hardChk[count($hardChk)-2].'.'.$hardChk[count($hardChk)-1];
		} else
			$hardChk = $hardChk[count($hardChk)-2].'.'.$hardChk[count($hardChk)-1];
		$this->AddWhere('AND (`'.$this->model.'`.`default_domain` LIKE "'.$this->escape_string((string)trim($url)).'" OR `'.$this->model.'`.`default_domain` LIKE "www.'.$this->escape_string((string)trim($url)).'" OR `'.$this->model.'`.`default_domain` LIKE "'.$this->escape_string((string)trim($hardChk)).'" OR `'.$this->model.'`.`default_domain` LIKE "www.'.$this->escape_string((string)trim($hardChk)).'")');
	}
}