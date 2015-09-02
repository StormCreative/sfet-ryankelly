<?php
/*
# MY ACCOUNT CONTROLLER
# by Danny Broadbent
*/
class MyAccountController extends BaseController {
	public function __construct() {
		parent::__construct();

		$this->cacheType('None');
	}

	public function view() {
		$this->unsetHeaderFooter();
		$this->setRenderView(false);

		if ($this->isAdmin($this->getCurrentUser())) {
			$this->redirect($this->main_url.'/admin/accounts');
		} else if ($this->userLoggedIn()) {
			$this->redirect($this->main_url.'/my-account/welcome');
		} else {
			$this->redirect($this->main_url.'/my-account/login');
		}
		exit;
	}

	public function welcome() {
		$this->forceLogin();
		$this->setTitle('Welcome back, '.$this->user['forename'].'!');
		
		$this->addData('approved', $this->user['approved']);
		
		$policy = $this->model('policy');
		$policy->FilterByUserId($this->getCurrentUser());
		$policy->FilterByActive();
		$policy->getCurrentPolicy();
		if ($policy->LoadRecords()) {
			while ($policy->Read()) {
				$this->addData('status', (($policy->f('status') == 'EXPIRED' || strtotime('+'.RENEWAL_DATE) < strtotime($policy->f('expiry_date'))) ? ucwords($policy->f('status')) : 'Renewal'));
				$this->addData('status_class', strtolower((($policy->f('status') == 'EXPIRED' || strtotime('+'.RENEWAL_DATE) < strtotime($policy->f('expiry_date'))) ? $policy->f('status') : 'renewal')));
				$this->addData('expiry_date', date('d/m/Y', strtotime($policy->f('expiry_date'))));
			}
		} else {
			$policy = $this->model('policy');
			$policy->FilterByUserId($this->getCurrentUser());
			$policy->FilterByStatus('INCOMPLETE');
			if ($policy->LoadRecords()) {
				while ($policy->Read()) {
					$this->addData('continue_application', $this->main_url.'/policy/'.($policy->f('progress')?$policy->f('progress'):'your-firm'));
				}
			} else {
				$this->addData('continue_application', $this->main_url.'/policy/your-firm');
			}
			$policy->FreeResult();

			$this->addData('status', false);
			$this->addData('status_class', false);
		}
		$policy->FreeResult();
	}
}