<?php
/*
# SITEMAP CONTROLLER
# by Danny Broadbent
*/
class SitemapController extends BaseController {
	public function __construct() {
		parent::__construct();
	}

	public function view() {
		$this->setView('sitemap');
	}
}