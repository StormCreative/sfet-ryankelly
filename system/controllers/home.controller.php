<?php
/*
# HOME CONTROLLER
# by Danny Broadbent
*/
class HomeController extends BaseController {
	public function __construct() {
		parent::__construct();
	}
	
	public function view() {
        
        $this->addStyle('https://fonts.googleapis.com/css?family=Montserrat');
        $this->addStyle('style');
        
        $this->addScript('jquery-2.1.4.min');
        $this->addScript('script');
        
		$this->setView('index');
	}
    
    public function success() {
    
        $this->addStyle('https://fonts.googleapis.com/css?family=Montserrat');
        $this->addStyle('style');
        
        $this->addScript('jquery-2.1.4.min');
        $this->addScript('script');
        
		$this->setView('index');
    }
    
    public function failure() {
    
        $this->addStyle('https://fonts.googleapis.com/css?family=Montserrat');
        $this->addStyle('style');
        
        $this->addScript('jquery-2.1.4.min');
        $this->addScript('script');
        
		$this->setView('index');
    }
}