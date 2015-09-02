<?php
class HomeController {
	public function __construct() {
		parent::__construct();
	}

	public function view() {
		$this->setView('front');
	}
}