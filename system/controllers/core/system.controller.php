<?php
/*
# SYSTEM CONTROLLER
# by Danny Broadbent
*/
abstract class SystemController {
	protected $bot_list = array("Teoma","alexa","froogle","Gigabot","inktomi","looksmart","URL_Spider_SQL","Firefly","NationalDirectory","Ask Jeeves","TECNOSEEK","InfoSeek","WebFindBot","girafabot","crawler","www.galaxy.com","Googlebot","Scooter","Slurp","msnbot","appie","FAST","WebBug","Spade","ZyBorg","rabaz","Baiduspider","Feedfetcher-Google","TechnoratiSnoop","Rankivabot","Mediapartners-Google","Sogou web spider","WebAlta Crawler");
	protected $isAdminPath = false;

	public $allowed_url = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ /-:.%0123456789";
	public $allowed_pic = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ /-_:.%0123456789";
	public $allowed_09 = "0123456789";
	public $allowed_az = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	public $allowed_az09 = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	public $allowed_dns = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-.";
	public $allowed_seo = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-. ";
	public $valid_ipaddress = "/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/";
	public $valid_hostname = "/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/";
	
	public $urlPass = ''; // Get path only (strip query strings)
	public $urlPassURI = ''; // Get server URI
	public $urlReferer = ''; // Check referer URL
	public $urlPagingChk = ''; // Check if page has pagination
	public $getPageNum = ''; // Page number (for pagination pages only)
	
	public $site = array(); // variables from site table in db
	public $main_url = ''; // Store verified main url
	public $user = NULL; // Stores current logged in users data
	public $_get = array(); // replaces $_GET data
	public $_post = array(); // replaces $_POST data
	public $_cookie = array(); // replaces $_COOKIE data
	public $_files = array(); // replaces $_FILES data
	public $_server = array(); // replaces $_SERVER data
	public $_session = array(); // replaces $_SESSION data
	public $empty_fields = array();
	
	private $title; // page title
	private $description; // page description
	private $keywords; // page keywords
	private $links = array(); // handles links (favicons, rss, etc)
	private $styles = array(); // handles stylesheets
	private $scripts = array(); // handles javascript files
	private $header_scripts = array(); // handles javascript files (in header)
	private $body_class = ''; // handles javascript files (in header)
	private $meta_tags = array(); // handles meta tags
	private $email = ''; // check valid email addresses
	private $http_response_code = 200; // HTTP response code (good for handling errors and redirections)
	
	protected $registry = NULL; // registered site variables
	protected $timezone = NULL;
	protected $ENV = NULL; // LIVE = production site, DEV = development site
	protected $lang = 'en'; // website language mode (en = default)
	protected $render_view = true; // Enable / Disable page render
	protected $allowed_file_types = array();
	
	public function __construct() {
		$this->registry = new Registry;

		$_GET = $this->clean($_GET);
		$_POST = $this->clean($_POST);
		$_REQUEST = $this->clean($_REQUEST);
		$_COOKIE = $this->clean($_COOKIE);
		$_FILES = $this->clean($_FILES);
		$_SERVER = $this->clean($_SERVER);
		$_SESSION = $this->clean($_SESSION);
		
		$this->_get = $_GET;
		$this->_post = $_POST;
		$this->_request = $_REQUEST;
		$this->_cookie = $_COOKIE;
		$this->_files = $_FILES;
		$this->_server = $_SERVER;
		$this->_session = $_SESSION;
		
		$this->chkENV();
		
		$this->htaccess();
		$this->phpini();

		// Store site variables from db
		$loadSite = $this->model('site');
		if ($loadSite->LoadRecords()) {
			while ($loadSite->Read()) {
				$this->site[$loadSite->f('attr')] = $loadSite->f('val');
			}
		}
		if (!defined('SYS_DOMAIN')) define("SYS_DOMAIN", $this->site['default_domain']);
		
		$this->main_url = 'http'.$this->chkSSL().'://'.(($this->site['default_domain'])?SYS_DOMAIN:$this->_server['HTTP_HOST']);
		
		$this->urlPass = preg_replace('/\?.*/', '', $this->_server['REQUEST_URI']);
		$this->urlPassURI = $this->_server['REQUEST_URI'];
		$this->urlReferer = (isset($this->_server['HTTP_REFERER'])) ? preg_replace('/\?.*/', '', str_replace($this->main_url, '', $this->_server['HTTP_REFERER'])) : '';
		$this->urlPagingChk = preg_replace('/^\/(.*?)\/[0-9]+/i', '/$1', $this->urlPass); // Remove paging from URL's
		$this->getPageNum = ((int)preg_replace('/^\/(.*?)\/([0-9]+)/i', '$2', $this->urlPass)>1) ? (int)preg_replace('/^\/(.*?)\/([0-9]+)/i', '$2', $this->urlPass) : 1; // Get Paging

		$urlAdminChk = explode('/', $this->urlPass);
		if (strpos((string)trim($urlAdminChk[1]), str_replace('/', '', (string)trim(URL_ADMIN))) !== false) {
			$this->isAdminPath = true;
		}

		if ($this->userLoggedIn()) {
			$user = $this->model('users');
			if ($user->LoadRecord($this->getCurrentUser())) {
				foreach ($user->getColumns() as $column) {
					$this->user[$column['name']] = $user->f($column['name']);
				}
			}
		} else {
			$this->user['id'] = 0;
		}

		$this->allowed_file_types = array('jpg','jpeg','gif','tiff','bmp','pdf','doc','xls','odt','docx','pptx','ods','ppt');
	}

	public function AllowedFileTypes() {
		return $this->allowed_file_types;
	}

	public function activeClass($url, $class='active') {
		if ($this->urlPass == '/'.$url) {
			return ' '.$class;
		}
	}

	public function isAdmin($userid=NULL) {
		if ($userid!=NULL) {
			return ((int)$this->user['group_id']>=8) ? true : false;
		} else
			return false;
	}

	public function isApproved($userid=NULL) {
		if ($userid!=NULL) {
			return ((int)$this->user['approved']==1) ? true : false;
		} else
			return false;
	}

	public function isSuperAdmin($userid=NULL) {
		if ($userid!=NULL) {
			return ((int)$this->user['group_id']>=9) ? true : false;
		} else
			return false;
	}

	// Get current logged in users id
	public function getCurrentUser() {
		if ($this->userLoggedIn()) {
			if (isset($this->_cookie['userId']) && (int)$this->_cookie['userId']>0)
				return $this->_cookie['userId'];
			else if (isset($this->_session['userId']) && (int)$this->_session['userId']>0)
				return $this->_session['userId'];
			else
				return 0;
		} else
			return 0;
	}

	// Check user is logged in or not
	public function userLoggedIn() {
		if (isset($this->_cookie['activeLogin']) && strlen((string)trim($this->_cookie['activeLogin']))>0) {
			$chkLogin = $this->model('users');
			$chkLogin->sessionChk($this->_cookie['userId'], $this->_cookie['username'], $this->_cookie['activeLogin']);
			if ($chkLogin->LoadRecords())
				return true;
			else
				return false;

			$chkLogin->FreeResult();
		} else if (isset($this->_session['activeLogin']) && strlen((string)trim($this->_session['activeLogin']))>0) {
			$chkLogin = $this->model('users');
			$chkLogin->sessionChk($this->_session['userId'], $this->_session['username'], $this->_session['activeLogin']);
			if ($chkLogin->LoadRecords())
				return true;
			else
				return false;

			$chkLogin->FreeResult();
		} else
			return false;
	}

	public function ErrorPage($errCode) {
		$this->setResponseCode($errCode);
		require_once(CTRL_PATH.'/error.controller.php');
		$load = new ErrorController;
		$load->view($this->getResponseCode());
		$load->renderView();
		exit;
	}

	// Force user to login (if not already logged in)
	protected function forceLogin($group=array(1)) {
		if (isset($this->_cookie['activeLogin']) && strlen((string)trim($this->_cookie['activeLogin']))>0) {
			$chkLogin = $this->model('users');
			$chkLogin->sessionChk($this->_cookie['userId'], $this->_cookie['username'], $this->_cookie['activeLogin']);
			if (!$chkLogin->LoadRecords() && !$this->userLoggedIn()) {
				$this->redirect($this->main_url.'/my-account/login') && exit;
			} else if ($this->isAdmin($this->getCurrentUser()) && preg_match('/^'.URL_ADMIN.'\/(.*)/', $this->urlPass)) {
				$this->ErrorPage(403);
			} else if (!in_array($this->user['group_id'], $group)) {
				$this->ErrorPage(403);
			}
		} else if (isset($this->_session['activeLogin']) && strlen((string)trim($this->_session['activeLogin']))>0) {
			$chkLogin = $this->model('users');
			$chkLogin->sessionChk($this->_session['userId'], $this->_session['username'], $this->_session['activeLogin']);
			if (!$chkLogin->LoadRecords() && !$this->userLoggedIn()) {
				$this->redirect($this->main_url.'/my-account/login') && exit;
			} else if (!$this->isAdmin($this->getCurrentUser()) && preg_match('/^'.URL_ADMIN.'\/(.*)/', $this->urlPass)) {
				$this->ErrorPage(403);
			} else if (!in_array($this->user['group_id'], $group)) {
				$this->ErrorPage(403);
			}
		} else if (!$this->userLoggedIn()) {
			$this->redirect($this->main_url.'/my-account/login') && exit;
		}
	}

	// Force admin login
	protected function forceAdminLogin() {
		$this->forceLogin(array(8,9,10));
	}

	// Enable / Disable rendering a view
	public function setRenderView($render=true) {
		$this->render_view = ($render)?true:false;
	}
	
	// Load model file
	protected function model($model) {
		$model = (string)trim($model);
		require_once(MODEL_PATH.'/'.$model.'.model.php');
		
		$model = explode('/', $model);
		$model = $model[count($model)-1];

		if (strpos($model,'_') !== false) {
			$model = str_replace('_', ' ', $model);
			$model = ucwords($model);
			$model = str_replace(' ', '', $model);
		} else
			$model = ucwords($model);

		$model = '$model = new '.$model.'Model();';
		eval($model);
		return $model;
	}
	
	// Load helper file
	protected function helper($helper) {
		$helper = (string)trim($helper);
		require_once(HELPER_PATH.'/'.$helper.'.helper.php');
		
		$helper = explode('/', $helper);
		$helper = $helper[count($helper)-1];

		if (strpos($helper,'_') !== false) {
			$helper = str_replace('_', ' ', $helper);
			$helper = ucwords($helper);
			$helper = str_replace(' ', '', $helper);
		} else
			$model = ucwords($helper);

		$helper = '$helper = new '.$helper.'Helper();';
		eval($helper);
		return $helper;
	}
	
	public function setTimezone($timezone='Europe/London') {
		$this->timezone = $timezone;
	}
	
	public function getTimezone() {
		return $this->timezone;
	}
	
	public function SetEmail($email) {
		$this->email = $email;
	}
	
	public function ValidateEmail() {
		$chk = (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) ? 0 : 1;
		$this->email = ''; // unset email for security
		return $chk;
	}
	
	public function chkENV() {
		$client_ip = $this->getClientIP();
		if ($this->isLocalIP($client_ip)) {
			$this->ENV = 'DEV';
		} else {
			$this->ENV = 'LIVE';
		}
	}
	
	public function chkSSL() {
		if(isset($this->_server['HTTPS']) && $this->_server['HTTPS'] == "on")
			return 's';
		
		if(isset($this->_server['HTTP_X_FORWARDED_PROTO']) && (string)$this->_server['HTTP_X_FORWARDED_PROTO'] == 'https')
			return 's';
		return '';
	}
	
	protected function redirect($url, $status = 302) {
		$this->setResponseCode($status);
		header('Location: '.str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url));
		exit;
	}
	
	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}
	
	public function __get($key=NULL) {
		return $this->registry->get($key);
	}
	
	public function br2nl($str) { // reverses nl2br PHP function
		return preg_replace("/<br[^>]*>\s*\r*\n*/is", "\n", $str);
	}
	
	protected function setLang($lang) {
		$this->lang = $lang;
	}
	
	public function getLang() {
		return $this->lang;
	}
	
	protected function setTitle($title) {
		$this->title = $title;
	}
	
	public function getTitle() {
		return $this->title;
	}
	
	protected function setDescription($description) {
		$this->description = $description;
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	protected function setKeywords($keywords) {
		$this->keywords = $keywords;
	}
	
	public function getKeywords() {
		return $this->keywords;
	}
	
	protected function addLink($href, $rel, $additional = '') {
		$this->links[md5($href)] = array(
			'href'       => $href,
			'rel'        => $rel,
			'additional' => $additional
		);
	}
	
	public function getLinks() {
		return $this->links;
	}
	
	public function unloadLinks() {
		$this->links = array();
	}
	
	protected function addMetaTag($content, $name = '', $httpequiv = '', $property = '', $charset = '') {
		$this->meta_tags[] = array(
			'content'    => $content,
			'name'       => $name,
			'property'   => $property,
			'http-equiv' => $httpequiv,
			'charset' => $charset
		);
	}
	
	public function getMetaTags() {
		return $this->meta_tags;
	}
	
	public function unloadMetaTags() {
		$this->meta_tags = array();
	}
	
	protected function addStyle($href, $rel = 'stylesheet', $media = 'screen', $type = 'text/css') {
		if (strpos($href,'http://') === false || strpos($href,'https://') === false) $href = $this->main_url.'/'.CSS_URL.'/'.$href.'.css';
		$this->styles[md5($href)] = array(
			'href'  => $href,
			'rel'   => $rel,
			'media' => $media,
			'type' => $type
		);
	}
	
	public function getStyles() {
		return $this->styles;
	}
	
	public function unloadStyles() {
		$this->styles = array();
	}
	
	protected function addScript($script, $datamain='', $type='') {
		if (strpos($script,'http://') === false || strpos($script,'https://') === false) $script = $this->main_url.'/'.JS_URL.'/'.$script.'.js';
		if (strpos($datamain,'http://') === false || strpos($datamain,'https://') === false) $datamain = $this->main_url.'/'.JS_URL.'/'.$datamain.'.js';
		$this->scripts[md5($script)] = array(
			'src' => $script,
			'data-main' => $datamain,
			'type' => $type
		);
	}
	
	public function getScripts() {
		return $this->scripts;
	}
	
	public function unloadScripts() {
		$this->scripts = array();
	}
	
	protected function addHeaderScript($script, $datamain='', $type='') {
		if (strpos($script,'http://') === false || strpos($script,'https://') === false) $script = $this->main_url.'/'.JS_URL.'/'.$script.'.js';
		if (strpos($datamain,'http://') === false || strpos($datamain,'https://') === false) $datamain = $this->main_url.'/'.JS_URL.'/'.$datamain.'.js';
		$this->header_scripts[md5($script)] = array(
			'src' => $script,
			'type' => $type
		);
	}
	
	public function getHeaderScripts() {
		return $this->header_scripts;
	}
	
	public function unloadHeaderScripts() {
		$this->header_scripts = array();
	}
	
	protected function addBodyClass($class='') {
		$this->body_class = $class;
	}
	
	public function getBodyClass() {
		return $this->body_class;
	}
	
	public function unloadBodyClass() {
		$this->body_class = '';
	}
	
	public function getTags() {
		$styles = $header_scripts = $scripts = $links = $meta_tags = '';
		foreach ($this->getHeaderScripts() as $script) {
			$header_scripts.='<script src="'.$script['src'].'" type="'.($script['type']?$script['type']:'text/javascript').'"></script>'."\n";
		}
		foreach ($this->getScripts() as $script) {
			$scripts.='<script src="'.$script['src'].'" type="'.($script['type']?$script['type']:'text/javascript').'" '.($script['data-main']?'data-main="'.$script['data-main'].'"':'').'></script>'."\n";
		}
		foreach ($this->getStyles() as $style) {
			$styles.='<link href="'.$style['href'].'" rel="'.$style['rel'].'" type="'.$style['type'].'" media="'.$style['media'].'" />'."\n";
		}
		foreach ($this->getLinks() as $link) {
			$links.='<link href="'.$link['href'].'" rel="'.$link['rel'].'" '.$link['additional'].' />'."\n";
		}
		foreach ($this->getMetaTags() as $meta_tag) {
			$meta_tags.='<meta'.(!is_null($meta_tag['name']) && strlen(trim($meta_tag['name']))>0?' name="'.$meta_tag['name'].'"':'').(!is_null($meta_tag['property']) && strlen(trim($meta_tag['property']))>0?' property="'.$meta_tag['property'].'"':'').(!is_null($meta_tag['http-equiv']) && strlen(trim($meta_tag['http-equiv']))>0?' http-equiv="'.$meta_tag['http-equiv'].'"':'').(!is_null($meta_tag['content']) && strlen(trim($meta_tag['content']))>0?' content="'.$meta_tag['content'].'"':'').(!is_null($meta_tag['charset']) && strlen(trim($meta_tag['charset']))>0?' charset="'.$meta_tag['charset'].'"':'').' />'."\n";
		}
		return array(
			'styles' => $styles,
			'scripts' => $scripts,
			'header_scripts' => $header_scripts,
			'links' => $links,
			'meta_tags' => $meta_tags
		);
	}

	public function EmptyFieldsArray($field, $array) {
		if (is_array($array)) {
			foreach ($array as $value) {
				if (is_array($value)) {
					$this->EmptyFieldsArray($field, $value);
				} else if (strlen((string)trim($value))==0) {
					if (!in_array($field, $this->empty_fields))
						$this->empty_fields[] = $field;
					return;
				}
			}
		} else {
			if (strlen((string)trim($array))==0) {
				if (!in_array($field, $this->empty_fields))
					$this->empty_fields[] = $field;
				return;
			}
		}
	}

	public function EmptyFields($fields) {
		foreach ($fields as $field => $value) {
			if (is_array($value)) {
				$this->EmptyFieldsArray($field, $value);
			} else if (strlen((string)trim($value))==0) {
				if (!in_array($field, $this->empty_fields))
					$this->empty_fields[] = $field;
			}
		}
	}
	
	public function bstdate() {
		// check if the gmt offset is defined
		if(defined('BST_FLAG')) {
			if (BST_FLAG) {
				$bst_offset = 1;
			} else {
				$bst_offset = 0;
			}
		} else {
			// if not, default to 0
			$bst_offset = 0;
		}
		return gmdate('Y-m-d H:i:s', time() + ($bst_offset*60*60));
	}
	
	public function clean($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				unset($data[$key]);
				$data[$this->clean($key)] = $this->clean($value);
			}
		} else {
			$data = htmlspecialchars($data, ENT_COMPAT, 'UTF-8');
		}
		return str_replace('&amp;', '&', $data);
	}
	
	public function isLocalIP($ip) {
		if (!empty($ip) && ip2long($ip)!=-1) {
			$reserved_ips = array (
				array('0.0.0.0','2.255.255.255'),
				array('10.0.0.0','10.255.255.255'),
				array('127.0.0.0','127.255.255.255'),
				array('169.254.0.0','169.254.255.255'),
				array('172.16.0.0','172.31.255.255'),
				array('192.0.2.0','192.0.2.255'),
				array('192.168.0.0','192.168.255.255'),
				array('255.255.255.0','255.255.255.255')
			);

			foreach ($reserved_ips as $r) {
				$min = ip2long($r[0]);
				$max = ip2long($r[1]);
				if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return true;
			}
			return false;
		} else {
			return false;
		}
	}
	
	public function getClientIP() {
		if ($this->isValidIP((isset($this->_server["HTTP_CLIENT_IP"]))?$this->_server["HTTP_CLIENT_IP"]:'')) {
			return $this->_server["HTTP_CLIENT_IP"];
		}
		foreach (explode(",",(isset($this->_server["HTTP_X_FORWARDED_FOR"]))?$this->_server["HTTP_X_FORWARDED_FOR"]:'') as $ip) {
			if ($this->isValidIP(trim($ip))) return $ip;
		}
		if (isset($this->_server["HTTP_X_FORWARDED"]) && $this->isValidIP($this->_server["HTTP_X_FORWARDED"])) {
			return $this->_server["HTTP_X_FORWARDED"];
		} else if (isset($this->_server["HTTP_FORWARDED_FOR"]) && $this->isValidIP($this->_server["HTTP_FORWARDED_FOR"])) {
			return $this->_server["HTTP_FORWARDED_FOR"];
		} else if (isset($this->_server["HTTP_FORWARDED"]) && $this->isValidIP($this->_server["HTTP_FORWARDED"])) {
			return $this->_server["HTTP_FORWARDED"];
		} else if (isset($this->_server["HTTP_X_FORWARDED"]) && $this->isValidIP($this->_server["HTTP_X_FORWARDED"])) {
			return $this->_server["HTTP_X_FORWARDED"];
		} else {
			return $this->_server["REMOTE_ADDR"];
		}
	}
	
	public function isValidIP($ip) {
		if (!empty($ip) && ip2long($ip)!=-1) {
			$reserved_ips = array (
				array('0.0.0.0','2.255.255.255'),
				array('10.0.0.0','10.255.255.255'),
				array('127.0.0.0','127.255.255.255'),
				array('169.254.0.0','169.254.255.255'),
				array('172.16.0.0','172.31.255.255'),
				array('192.0.2.0','192.0.2.255'),
				array('192.168.0.0','192.168.255.255'),
				array('255.255.255.0','255.255.255.255')
			);
			
			foreach ($reserved_ips as $r) {
				$min = ip2long($r[0]);
				$max = ip2long($r[1]);
				if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;
			}
			return true;
		} else {
			return false;
		}
	}

	public function is_serialized($data, $strict = true) {
		// if it isn't a string, it isn't serialized.
		if (!is_string($data))
				return false;

		$data = trim($data);

		if ('N;' == $data)
			return true;

		if (strlen($data)<4)
			return false;

		if (':' !== $data[1])
			return false;

		if ($strict) {
			$lastc = substr($data, -1);
			if (';' !== $lastc && '}' !== $lastc)
				return false;
		} else {
			$semicolon = strpos($data, ';');
			$brace = strpos($data, '}');
			// Either ; or } must exist.
			if (false === $semicolon && false === $brace)
				return false;
			// But neither must be in the first X characters.
			if (false !== $semicolon && $semicolon < 3)
				return false;
			if (false !== $brace && $brace < 4)
				return false;
		}

		$token = $data[0];

		switch ($token) {
			case 's':
				if ($strict) {
					if ('"' !== substr($data, -2, 1))
						return false;
				} else if ( false === strpos($data, '"'))
					return false;
			case 'a':
			case 'O':
				return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
			case 'b':
			case 'i':
			case 'd':
				$end = ($strict) ? '$' : '';
				return (bool)preg_match("/^{$token}:[0-9.E-]+;$end/", $data);
		}

		return false;
	}
	
	public function getResponseCode() {
		return $this->http_response_code;
	}

	public function Error($err) {
		//$err = addslashes($err);
		$err = str_replace("\n", '\n', $err);
		$err = preg_replace("/[^A-Za-z0-9\s]=/i", '', $err);
		echo '<div style="position:absolute;top:0;left:0;width:100%;text-align:center;background-color:#FEE;padding:10px;box-sizing:border-box;z-index:9999;"><strong>ERROR:</strong> '.$err.'</div>';
	}
	
	protected function setResponseCode($code=200) { // Default 200 response code
		switch ($code) {
			case 100: $text = 'Continue'; break;
			case 101: $text = 'Switching Protocols'; break;
			case 200: $text = 'OK'; break;
			case 201: $text = 'Created'; break;
			case 202: $text = 'Accepted'; break;
			case 203: $text = 'Non-Authoritative Information'; break;
			case 204: $text = 'No Content'; break;
			case 205: $text = 'Reset Content'; break;
			case 206: $text = 'Partial Content'; break;
			case 300: $text = 'Multiple Choices'; break;
			case 301: $text = 'Moved Permanently'; break;
			case 302: $text = 'Moved Temporarily'; break;
			case 303: $text = 'See Other'; break;
			case 304: $text = 'Not Modified'; break;
			case 305: $text = 'Use Proxy'; break;
			case 400: $text = 'Bad Request'; break;
			case 401: $text = 'Unauthorized'; break;
			case 402: $text = 'Payment Required'; break;
			case 403: $text = 'Forbidden'; break;
			case 404: $text = 'Not Found'; break;
			case 405: $text = 'Method Not Allowed'; break;
			case 406: $text = 'Not Acceptable'; break;
			case 407: $text = 'Proxy Authentication Required'; break;
			case 408: $text = 'Request Time-out'; break;
			case 409: $text = 'Conflict'; break;
			case 410: $text = 'Gone'; break;
			case 411: $text = 'Length Required'; break;
			case 412: $text = 'Precondition Failed'; break;
			case 413: $text = 'Request Entity Too Large'; break;
			case 414: $text = 'Request-URI Too Large'; break;
			case 415: $text = 'Unsupported Media Type'; break;
			case 500: $text = 'Internal Server Error'; break;
			case 501: $text = 'Not Implemented'; break;
			case 502: $text = 'Bad Gateway'; break;
			case 503: $text = 'Service Unavailable'; break;
			case 504: $text = 'Gateway Time-out'; break;
			case 505: $text = 'HTTP Version not supported'; break;
			default:
				exit('Unknown http status code "' . htmlentities($code) . '"');
			break;
		}
		$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
		header($protocol . ' ' . $code . ' ' . $text);
		$GLOBALS['http_response_code'] = $code;
		$this->http_response_code = $code;
	}
	
	private function htaccess() {
		$htaccess = (
			($this->ENV=='LIVE') ?
				'<IfModule mod_suphp.c>'."\n".
				'suPHP_ConfigPath /home/newsftp/new_html/'."\n".
				'</IfModule>'."\n".
				''."\n".
				'Header unset Pragma'."\n".
				'FileETag None'."\n".
				'Header unset ETag'."\n".
				''."\n".
				'# 1 YEAR'."\n".
				'<filesMatch "\.(ico|pdf|flv)$">'."\n".
				'Header set Cache-Control "max-age=29030400, public"'."\n".
				'</filesMatch>'."\n".
				'# 1 WEEK'."\n".
				'<filesMatch "\.(jpg|jpeg|png|gif|swf|bmp)$">'."\n".
				'ExpiresActive on'."\n".
				'ExpiresDefault "access plus 1 month"'."\n".
				'Header set Cache-Control "max-age=604800, public"'."\n".
				'</filesMatch>'."\n".
				'# 2 DAYS'."\n".
				'<filesMatch "\.(xml|txt|css|js)$">'."\n".
				'Header set Cache-Control "max-age=172800, proxy-revalidate"'."\n".
				'</filesMatch>'."\n".
				'# 1 MIN'."\n".
				'<filesMatch "\.(html|htm|xsl)$">'."\n".
				'Header set Cache-Control "max-age=60, private, proxy-revalidate"'."\n".
				'</filesMatch>'."\n".
				''."\n".
				'<IfModule mod_expires.c>'."\n".
				'	ExpiresActive on'."\n".
				'	ExpiresByType image/jpg "access plus 1 month"'."\n".
				'	ExpiresByType image/jpeg "access plus 1 month"'."\n".
				'	ExpiresByType image/gif "access plus 1 month"'."\n".
				'	ExpiresByType image/png "access plus 1 month"'."\n".
				'</IfModule>'."\n".
				''."\n".
				'# compress text, html, javascript, css, xml:'."\n".
				'AddOutputFilterByType DEFLATE text/plain'."\n".
				'AddOutputFilterByType DEFLATE text/html'."\n".
				'AddOutputFilterByType DEFLATE text/xml'."\n".
				'AddOutputFilterByType DEFLATE text/css'."\n".
				'AddOutputFilterByType DEFLATE application/xml'."\n".
				'AddOutputFilterByType DEFLATE application/xhtml+xml'."\n".
				'AddOutputFilterByType DEFLATE application/rss+xml'."\n".
				'AddOutputFilterByType DEFLATE application/javascript'."\n".
				'AddOutputFilterByType DEFLATE application/x-javascript'."\n".
				''."\n".
				'# Redirect Trafic To www.'."\n".
				'#RewriteCond %{HTTP_HOST} !^www\.'."\n".
				'#RewriteRule ^(.*)$ http://www.%{HTTP_HOST}/$1 [R=301,L]'."\n".
				''."\n".
				'RewriteEngine on'."\n".
				'RewriteCond %{REQUEST_FILENAME} !-f'."\n".
				'RewriteRule . index.php [L]'
			:
				'RewriteEngine on'."\n".
				'RewriteCond %{REQUEST_FILENAME} !-f'."\n".
				'RewriteRule . index.php [L]'
		);
		
		file_put_contents(DIR_PATH.'/.htaccess', $htaccess);
	}
	
	private function phpini() {
		$phpini = (($this->ENV=='LIVE') ?
			'max_execution_time = 30'."\n".
			'max_input_time = 60'."\n".
			'memory_limit = 1024M'."\n".
			'upload_max_filesize = 2048M'."\n".
			'post_max_size = 1000M'."\n".
			'max_input_vars = 3000'."\n".
			'file_uploads=1'."\n".
			'allow_url_fopen = On'."\n".
			'error_reporting = 0'."\n".
			'session.cookie_httponly = true'
		:
			'max_execution_time = 30'."\n".
			'max_input_time = 60'."\n".
			'memory_limit = 1024M'."\n".
			'upload_max_filesize = 2048M'."\n".
			'post_max_size = 1000M'."\n".
			'max_input_vars = 3000'."\n".
			'file_uploads=1'."\n".
			'allow_url_fopen = On'."\n".
			'error_reporting = E_ALL'."\n".
			'session.cookie_httponly = true'
		);
		file_put_contents(DIR_PATH.'/php.ini', $phpini);
	}
	
	public function getHeader($output) {
		$__header = '<!DOCTYPE html>'."\n".
					'<!-- Microdata markup added by Google Structured Data Markup Helper. -->'."\n".
					'<!--[if IE 7]><html lang="en" class="ie7"><![endif]-->'."\n".
					'<!--[if IE 8]><html lang="en" class="ie8"><![endif]-->'."\n".
					'<!--[if IE 9]><html lang="en" class="ie9"><![endif]-->'."\n".
					'<!--[if !IE]><!-->'."\n".
					'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-gb" lang="en-gb">'."\n".
					'<!--<![endif]-->'."\n".
					'<head>'."\n".
					'<title>'.((strlen((string)trim($this->getTitle()))>0) ? $this->getTitle().' - '.$this->site['name'] : $this->site['name']).'</title>'."\n".
					((strlen(trim($output['meta_tags']))>0)?$output['meta_tags']:'').
					((strlen(trim($output['links']))>0)?$output['links']:'').
					((strlen(trim($output['styles']))>0)?$output['styles']:'').
					((strlen(trim($output['header_scripts']))>0)?$output['header_scripts']:'').
					((!$this->isAdminPath) ? 
					'<script type="application/ld+json">'."\n".
					'	{'."\n".
					'		"@context" : "http://schema.org",'."\n".
					'		"@type" : "Organization",'."\n".
					'		"name" : "'.$this->site['name'].'",'."\n".
					'		"legalName" : "'.$this->site['legal_name'].'",'."\n".
					'		"alternateName" : "'.$this->site['name'].'",'."\n".
					'		"taxID" : "'.$this->site['reg_no'].'",'."\n".
					'		"url" : "'.$this->main_url.'",'."\n".
					'		"logo": "'.$this->main_url.'/images/logo.png",'."\n".
					'		"address" : "'.str_replace(",\n", ", ", $this->site['address']).'",'."\n".
					'		"location" : "'.str_replace(",\n", ", ", $this->site['address']).'",'."\n".
					'		"email" : "'.$this->site['email'].'",'."\n".
					'		"sameAs" : ['."\n".
					'			"'.$this->site['twitter'].'",'."\n".
					'			"'.$this->site['linkedin'].'"'."\n".
					'		],'."\n".
					'		"foundingDate" : "'.$this->site['founded_date'].'",'."\n".
					'		"foundingLocation" : "'.str_replace(",\n", ", ", $this->site['address']).'"'."\n".
					'	}'."\n".
					'</script>'."\n"
					:'').
					'</head>'."\n".
					'<body tabindex="1"'.(($this->urlPass == "/" || strlen((string)trim($this->body_class))>0)?'class="'.(($this->urlPass == "/") ? 'landing '.$this->body_class : $this->body_class).'"':'').'>'."\n";
		
		return $__header;
	}
	
	public function getFooter($output) {
		$__footer = ((!$this->isAdminPath) ? "<!-- Google Analytics -->\n".
					"<script>\n".
					"(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){\n".
					"(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),\n".
					"m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)\n".
					"})(window,document,'script','//www.google-analytics.com/analytics.js','ga');\n".
					"ga('create', '".$this->site['google_analytics_id']."', 'auto');\n".
					"ga('send', 'pageview');\n".
					"</script>\n".
					"<!-- End Google Analytics -->\n":'').
					$output['scripts'].
					"</body>\n</html>";
					
		return $__footer;
	}
}