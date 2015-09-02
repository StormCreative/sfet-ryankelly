<?php
/*
# BASE CONTROLLER
# by Danny Broadbent
*/
class BaseController extends SystemController {
	private $show_env = false; // Show live / development status
	
	protected $cache = true; // caches pages to improve performance db load
	protected $cacheLvl = CACHE_LVL; // Full = everything, Partial = template only (ignores header/footer), None = cache disabled
	protected $use_header = true; // show footer
	protected $use_footer = true; // show header
	protected $data = array(); // PHP variables to pass through to template files
	protected $tpl_file = 'front'; // default template file
	
	public function __construct() {
		parent::__construct();
		
		$this->setTimezone(); // default = Europe / London
		
		// Log Crawl Requests
		foreach ($this->bot_list as $bot) {
			if (strstr(strtolower($_SERVER['HTTP_USER_AGENT']), strtolower($bot))) {
				$logCrawlReq = $this->model('crawl_log');
				$logCrawlReq->SetField('path', $urlPass);
				$logCrawlReq->SetField('referer', $_SERVER['HTTP_REFERER']);
				$logCrawlReq->SetField('bot', strtolower($bot));
				$logCrawlReq->SetField('http_code', http_response_code());
				$logCrawlReq->SetField('load_time', microtime(true) - $page_load_start_time);
				$logCrawlReq->SetField('timestamp', time());
				$logCrawlReq->SaveRecord();
			}
		}
		
		if (isset($this->_get['show']) && $this->_get['show'] == 'env') $this->show_env = true;
		
		// Setup default styles, meta tags and scripts
		$this->addMetaTag($this->site['audience'], 'audience');
		$this->addMetaTag($this->site['name'], 'author');
		$this->addMetaTag($this->site['name'], 'copyright');
		$this->addMetaTag($this->site['name'], 'publisher');
		$this->addMetaTag('Index, Follow', 'robots');
		$this->addMetaTag('5', 'revisit-after');
		$this->addMetaTag('en-gb', 'content-language');
		$this->addMetaTag('width=1200', 'viewport');
		$this->addMetaTag('text/html; charset=utf-8', NULL, 'Content-Type');
		$this->addMetaTag('en-gb', NULL, 'content-language');
		$this->addMetaTag('false', NULL, 'imagetoolbar');
		$this->addMetaTag('IE=9', NULL, $httpequiv = 'X-UA-Compatible');
		if (isset($this->site['facebook_page_id']) && strlen((string)trim($this->site['facebook_page_id']))>0)
			$this->addMetaTag($this->site['facebook_page_id'], NULL, NULL, 'fb:page_id');
		
		// Scripts
		$this->addScript('require.min', 'development/main');
		$this->addHeaderScript('utils/modernizr.min');
		
		// Styles
		$this->addStyle('layout');
		$this->addStyle('jqueryui');
			
		if (file_exists(IMG_PATH.'/favicon.png'))
			$this->addLink($this->main_url.'/images/favicon.png', 'shortcut icon');
		if (isset($this->site['google+']) && strlen((string)trim($this->site['google+']))>0)
			$this->addLink(trim($this->site['google+']), 'publisher');
		$this->addLink($this->main_url, 'alternate', 'hreflang="'.$this->lang.'"');
	}
	
	// Unset header/footer usefull for plain/custom pages
	public function unsetHeaderFooter() {
		$this->use_header = false;
		$this->use_header = false;
	}
	
	// Add PHP variables to be accessible via template
	public function addData($key, $val) {
		$this->data[$key] = $val;
	}
	
	// Get PHP variable by key or entire array if key not set
	public function getData($key = NULL) {
		if ($key != NULL)
			if (isset($this->data[$key]))
				return $this->data[$key];
			else
				return false;
		else
			return $this->data;
	}
	
	// Resets PHP variables for template
	public function unloadData() {
		$this->data = array();
	}
	
	// Set template file to load (include path)
	public function setView($view) {
		$this->tpl_file = $view;
	}

	public function cacheType($type=CACHE_LVL) {
		$this->cacheLvl = $type;
	}
	
	// Render page (also handles page caching)
	public function renderView() {
		if ($this->render_view) {
			$ctrlPath = CTRL_PATH;
			$tplPath = TPL_PATH;
			$globalPath = COMMON_PATH;
			if ($this->isAdminPath) {
				 // We always want the latest on admin so lets disable cache globally
				$this->cacheType('None');
				 // Change directories to admin paths
				$ctrlPath = CTRL_ADMIN;
				$tplPath = TPL_ADMIN;
				$globalPath = COMMON_ADMIN;
			}
			
			$cachedir=LANG_PATH.'/'.$this->lang.'/cache';
			$file=$cachedir."/".sha1($this->urlPass.$this->lang);
			
			$this->addMetaTag($this->getDescription(), 'description');
			$this->addMetaTag($this->getKeywords(), 'keywords');
			$this->addData('urlPass', $this->urlPass);
			$this->addData('site', $this->site);
			$this->addData('page', $this);
			$this->addData('body_class', $this->getBodyClass());
			$this->addData('title', $this->getTitle());
			$this->addData('main_url', $this->main_url);
			$this->addData('user', $this->user);
			$this->addData('is_admin', $this->isAdmin($this->user['id']));
			$this->addData('active', $this);
			
			$output = $this->getTags();
			
			date_default_timezone_set($this->getTimezone());
			
			$MYSQL_ENV = 'LIVE';
			if (MYSQL_USE == 'AUTO' && $this->ENV == 'DEV')
				$MYSQL_ENV = 'DEV';
			else if (MYSQL_USE == 'LOCAL')
				$MYSQL_ENV = 'DEV';
			
			if ($this->show_env) {
				$dbinfo = $this->model('core/database');
				$dbinfo->Query('SELECT VERSION() as mysql_version');
				$dbinfo->Read();
				echo '<div style="position:absolute;top:0;left:0;width:100%;text-align:center;background-color:#FEE;padding:10px;box-sizing:border-box;z-index:9999;"><strong>ENVIRONMENT:</strong> '.$this->ENV.' &nbsp;|&nbsp; <strong>MYSQL ENV:</strong> '.($MYSQL_ENV=='DEV'?'LOCAL':'LIVE').' &nbsp;|&nbsp; <strong>PHP VERSION:</strong> '.phpversion().' &nbsp;|&nbsp; <strong>MYSQL VERSION:</strong> '.(($dbinfo->f('mysql_version'))?$dbinfo->f('mysql_version'):mysql_get_server_info()).'</div>';
			}
			
			extract($this->getData());
			
			if (!file_exists($tplPath.'/'.$this->tpl_file.'.php')) {
				$this->Error('Failed to load view: '.$tplPath.'/'.$this->tpl_file.'.php, please create one!');
				exit;
			}

			if ($this->cacheLvl != 'Full' && $this->use_header) {
				$this->setResponseCode(200);
				echo $this->getHeader($output);
				require_once($globalPath.'/header.php');
			}
			
			if (file_exists($file) && filemtime($file)>strtotime("-1 day") && !isset($_GET["nocache"]) && $this->cacheLvl != 'None' && $this->ENV != 'DEV') {
				echo unserialize(file_get_contents($file));
			} else {
				if ($this->cacheLvl == 'Full' && $this->use_header) {
					ob_start();
					$this->setResponseCode(200);
					echo $this->getHeader($output);
					require_once($globalPath.'/header.php');
				}
				
				if ($this->cacheLvl != 'None' && $this->cacheLvl != 'Full') {
					ob_start();
				}

				require($tplPath.'/'.$this->tpl_file.'.php');
				
				if ($this->cacheLvl != 'None' && $this->cacheLvl != 'Full') {
					$outputData = ob_get_clean();
					echo $outputData;
				}
				
				if ($this->cacheLvl == 'Full' && $this->use_footer) {
					require_once($globalPath.'/footer.php');
					echo $this->getFooter($output);
				}
				
				if ($this->cacheLvl == 'Full') {
					$page = ob_get_clean();

					file_put_contents($file, serialize($page));
					echo $page;
				}
			}

			if ($this->cacheLvl != 'Full' && $this->use_footer) {
				require_once($globalPath.'/footer.php');
				echo $this->getFooter($output);
			}
		}
		exit;
	}
}