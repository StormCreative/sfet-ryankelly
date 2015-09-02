<?php
/*
# ROOT MODEL
# by Danny Broadbent

Base for database model + SystemController connector file

*/
abstract class RootModel extends SystemController {
	public $debug = 0;
	public $RowData = false;
	// debug switch (new)
	protected $DebugOn = false;
	protected $silentModeFlag = false; // whether errors are reported in the front end interface
	protected $silentModeCallbackObject = null; // object where callback is in scope
	protected $silendModeCallbackMethod = ''; // method name to call on callback object
	protected $lastError = ''; // the last error encountered
	// field defenition and whether or not quotes should be added when saving
	protected $saveDef = false;
	// table name and primary key defenitions
	protected $tableName = false;
	protected $primaryKey = false;
	// the fields to order by (including a set of defaults if none are specified)
	protected $orderFields = false;
	protected $defaultOrderFields = false;
	// fields to group by
	protected $groupFields = false;
	// the record limit
	protected $recordLimit = false;
	protected $recordOffset = false;
	// if the record saved was a new record
	protected $wasNewRecord = false;
	// where statements to add to select query
	protected $whereStatements = false;
	protected $havingStatements = false;
	// joins to add to select query
	protected $joinStatements = false;
	// sql that will extract class data
	protected $loadRecordsSql = false;
	// includes deleted records in generic query
	protected $includeDeletedRecordsFlag = false;
	// includes all fields from the current table in the query
	protected $includeAllFieldsFlag = true;
	// statements that will go between the SELECT and the output fields (e.g. DISTINCT)
	protected $postSelectKeywords = false;
	// additional fields to be outputted in the generic query
	protected $outputFields = false;
	// mainly used to force record creation if the primary key is not auto generated.
	protected $forceNewRecord = false;
	// indicates a single record has been loaded by primary key value (usefull for overiding general/list view filters)
	protected $filteredByPkValue = false;
	// values used in paging of results
	protected $numberOfPages = 0;
	protected $currentPage = 0;
	// whether to autoset values such as created/updated users/timestamps
	protected $autoValues = true;
	// linkid to reporting database
	protected $reportingConnection = false;
	// indicates whether the query cache should be bypassed for the select query
	protected $ignoreQueryCacheFlag = false;
	
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
	}

	public function chkENV() {
		$client_ip = $this->getClientIP();
		if ($this->isLocalIP($client_ip)) {
			$this->ENV = 'DEV';
		} else {
			$this->ENV = 'LIVE';
		}
	}

	// enables / disables silent mode
	public function SilentMode($bool = true, $useGlobal = false) {
		$this->silentModeFlag = $bool;
		$this->silentModeCallbackObject = null;
		$this->silendModeCallbackMethod = '';
		if ($useGlobal) {
			$GLOBALS['rootSilentModeFlag'] = $bool;
			$GLOBALS['rootSilentModeCallbackObject'] = null;
			$GLOBALS['rootSilentModeCallbackMethod'] = '';
		}
	}
	
	// enables silent mode and sets a callback public function to be used
	public function SetSilentCallBack(&$callbackObject, $callbackMethod, $useGlobal = false) {
		$this->SilentMode(true, $useGlobal);
		$this->silentModeCallbackObject = &$callbackObject;
		$this->silendModeCallbackMethod = $callbackMethod;
		if ($useGlobal) {
			$GLOBALS['rootSilentModeCallbackObject'] = &$callbackObject;
			$GLOBALS['rootSilentModeCallbackMethod'] = $callbackMethod;
		}
	}
	
	// checks to see if there are any global settings for silent mode
	public function CheckSilentGlobal() {
		if (isset($GLOBALS['rootSilentModeFlag'])) {
			if ($GLOBALS['rootSilentModeFlag']) {
				$this->silentModeFlag = true;
				$this->silentModeCallbackObject = &$GLOBALS['rootSilentModeCallbackObject'];
				$this->silendModeCallbackMethod = $GLOBALS['rootSilentModeCallbackMethod'];
			}
		}
	}
	
	// gets the last error that occured
	public function GetLastError() {
		return $this->lastError;
	}
	
	public function debug($text) {
		if ($this->debug) {
			print "<pre><li>DEBUG:".strtoupper(get_class($this)).": $text</pre>";
		}
	}
	
	// turn on debugging
	public function EnableDebug() {
		$this->DebugOn = true;
	}
	
	// output debug text on screen
	public function ShowDebugText($debugText) {
		if ($this->DebugOn) {
			echo "<textarea rows='10' cols='60' style='font-size: 10px;'>".$debugText."</textarea>";
		}
	}
	
	public function Error($err) {
		//$err = addslashes($err);
		$err = str_replace("\n", '\n', $err);
		$err = preg_replace("/[^A-Za-z0-9\s]=/i", '', $err);
		echo '<div style="position:absolute;top:0;left:0;width:100%;text-align:center;background-color:#FEE;padding:10px;box-sizing:border-box;z-index:9999;"><strong>ERROR:</strong> '.$err.'</div>';
	}
	
	// logs / displays system errors that occur
	public function SystemError($err) {
		// check global settings for silent mode
		$this->CheckSilentGlobal();
		
		// when in silent mode, simply store the last error generated
		if ($this->silentModeFlag == true) {
			$this->lastError = $err;
			// trigger the callback if defined
			if ($this->silentModeCallbackObject !== null && $this->silendModeCallbackMethod) {
				$methodName = $this->silendModeCallbackMethod;
				$this->silentModeCallbackObject->$methodName($this);
			}
		} else {
			//print $err;
			$this->Error($err);
			exit;
		}
	}
	
	// checks that the supplied text is a valid email address
	public function IsValidEmailAddress($checkValue) {
		if (strpos($checkValue, '@') !== FALSE && strpos($checkValue, '.') !== FALSE) {
			return true;
		} else {
			return false;	
		}
	}
	
	public function ObtainValidEmailAddress($email) {
		if ($this->IsValidEmailAddress($email)) return $email;
		else return "";
	}
	
	public function ObtainValidMobileNumber($mobileNumber) {
		$formatResult = false;
		// remove all spaces & country code
		$mobileNumber = str_replace(' ', '', $mobileNumber);
		$mobileNumber = str_replace('0044', '', $mobileNumber);
		// check if the number is numeric and not empty
		if (is_numeric($mobileNumber) && !empty($mobileNumber)) {
			// convert to an integer to remove leading zeros
			$mobileNumber = $mobileNumber + 0;
			// convert back to a string
			settype($mobileNumber, "string");
			// if the first char is '7' or '9' assume a valid mobile prefix
			if (substr($mobileNumber, 0, 1) == '7' || substr($mobileNumber, 0, 1) == '9') {
				// if the number is the correct length
				if (strlen($mobileNumber) == 10) {
					// set to return with the country code prepended
					$formatResult = '0044'.$mobileNumber;
				}
			}
		}
		
		return $formatResult;
	}
	
	public function ukTime($dtFormat) {
		$hour	= date("H") + gmt_server_offset; 
		$day	= date("d");
		/* 
		if ($ora >=24) {
			$ora -= 24; 
			$giorno += 1;
		}
		*/
		
		if($dtFormat == 1) return date("Y-m")."-".$day." ".$hour.date(":i:s");
		else if($dtFormat == 2)	return date("Y-m")."-".$day;
		else if($dtFormat == 3)	return date("D ".$day." M Y");
		else return $hour.date(":i:s");
	}
	
	// loop through an array setting the 'current' value to the index's key
	// returns the read status and resets the array once it reaches the end
	public function ArrayRead(&$dataArray, &$currentDataValue) {
		if (current($dataArray) !== false) {
			$currentDataValue = key($dataArray);
			next($dataArray);
			return true;
		}
		reset($dataArray);
		return false;
	}
	
	public function CleanNumeric($inputString) {
		return preg_replace("[^0-9]", '', $inputString);
	}
	
	public function CleanAlphaNumeric($inputString) {
		return preg_replace("/[^A-Za-z0-9_-]+/", '', $inputString);
	}
	
	public function GetRandomString($chars = 1) {
		$randomString = '';
		while (strlen($randomString) < $chars) {
			$randomString .= chr(rand(65, 90));
		}
		return $randomString;
	}
}