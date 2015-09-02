<?php
/*
# DATABASE MODEL
# by Danny Broadbent
*/
class DatabaseModel extends RootModel {
	private $databaseStatus = 0;
	private $_db_linkid = 0;
	private $_db_qresult = 0; // current result from recordset
	private $NextRowNumber = 0;
	private $RowCount = 0;
	private $EOF = true;
	private $globalTransEnabled = false;
	private $currentHost = '';
	private $currentUser = '';
	private $currentPassword = '';
	private $currentDb = '';
	private $dbSelectRetry = 0;
	private $tableLocks = false;
	private $queryDebug = 0; // success (1) or failure (0) of the last query ran
	private $lastQueryResult = null;
	private $rowArrayType = false;
	protected $rec_per_page = array();
	
	protected $mysqli = NULL;
	
	public $debug = 1;
	public $RowData = array();
	
	public function __construct($keepConnection = false) {
		parent::__construct();

		$MYSQL_ENV = 'LIVE';
		if (MYSQL_USE == 'AUTO' && $this->ENV == 'DEV')
			$MYSQL_ENV = 'DEV';
		else if (MYSQL_USE == 'LOCAL')
			$MYSQL_ENV = 'DEV';

		if ($MYSQL_ENV == 'DEV') {
			$host = MYSQL_HOST_LOCAL;
			$user = MYSQL_USER_LOCAL;
			$pass = MYSQL_PASSWORD_LOCAL;
			$db = MYSQL_DATABASE_LOCAL;
			$port = MYSQL_PORT_LOCAL;
		} else {
			$host = MYSQL_HOST;
			$user = MYSQL_USER;
			$pass = MYSQL_PASSWORD;
			$db = MYSQL_DATABASE;
			$port = MYSQL_PORT;
		}
		
		$this->rec_per_page = array(10, 25, 50, 100, 200);

		$this->database($host, $user, $pass, $db, $port, $keepConnection);
	}
	
	protected function database($host, $user, $pass, $db, $port, $keepConnection = false) {
		$this->currentHost = $host;
		$this->currentUser = $user;
		$this->currentPassword = $pass;
		$this->currentDb = $db;
		
		$this->databaseStatus = 0;
		if ($db != "" && $host !="" && $user !="" && $pass !="") {
			if ($db != $db && !$keepConnection) {
				$newlink = true;
				register_shutdown_function(array(&$this, "Close"));
			}
			else $newlink = false;
			
			if ($this->Open($host, $user, $pass, $db, $port, $newlink)) {
				if (!($this->databaseStatus = $this->SelectDB($db))) {
					if ($this->dbSelectRetry < 2) {
						$this->dbSelectRetry++;
						$this->Close();
						sleep(2);
						$this->database($host, $user, $pass, $db, $port, $keepConnection);
					} else
						$this->SystemError("Database selection error");
				}
			} else
				$this->SystemError("Database Connection Error");
		} else
			$this->SystemError("Database details missing");	
	}
	
	public function Open($host, $user, $pass, $db, $port, $newlink=false) {
		$this->mysqli = new mysqli($host, $user, $pass, $db, ((int)$port>0)?$port:3306);
		$this->_db_linkid = $this->mysqli->thread_id;
		if ($this->mysqli->connect_errno) {
			$this->SystemError("Connect failed: ".$this->mysqli->connect_error);
			exit;
		} else
			return $this->_db_linkid;
	}
	
	public function escape_string($string) {
		return $this->mysqli->escape_string($string);
	}
	
	public function FreeResult() {
		if (is_resource($this->_db_qresult) && $this->_db_qresult != 0)
			$this->_db_qresult = 0; // Free result
		return;
	}
	
	public function Close() {
		$this->FreeResult();
		$this->mysqli->kill($this->_db_linkid);
		$this->mysqli->close();
	}
	
	public function SelectDB($dbname) {
		return ($this->mysqli->select_db($dbname) == true) ? 1 : 0;
	}
	
	public function Read($querystr = "") {
		if ($querystr) {
			if ($this->Query($querystr)) return $this->ReadRow();
			else return 0;
		} else {
			return $this->ReadRow();
		}
	}
	
	// returns the status of the last query run
	public function GetLastQueryResult() {
		return $this->lastQueryResult;
	}
	
	// executes the specified query on the current database connection
	public function Query($querystr, $resetRowData = true) {
		// reset the last error encountered
		$this->lastError == '';
		// if using query debug mode, write query to disk
		/*if ($this->queryDebug == 1) {
			$file = fopen("Query_Debug.log","a+");
			fwrite($file, "\r\n-------------------------------------------------------------\r\n");
			fwrite($file, $querystr);
			fclose($file);
		}*/
		
		$result = $this->mysqli->query($querystr); 
		if ($result === FALSE){
			$this->lastQueryResult = 0;
			// if transactions are being used, rollback the changes on error
			if ($this->globalTransEnabled) {
				$this->dbRoll();
				$this->UnlockTables(); // also make sure any table locks are released
			}
			$this->_db_qresult = 0;
			if (!$this->mysqli->query("SET @a:='this will not work'"))
				$this->SystemError("Can't do query : $querystr\n\n".$this->mysqli->errno().": ".$this->mysqli->error);
		} else {
			$this->lastQueryResult = 1;
			$this->FreeResult();
			if ($resetRowData) {
				$this->RowData = array();
			}
			$this->_db_qresult = $result;
			$this->RowCount = (is_resource($result)) ? $result->num_rows : false;
			if (!$this->RowCount) { 
				// The query was probably an INSERT/REPLACE etc. 
				$this->RowCount = 0;
			}
		}
		return $this->lastQueryResult;
	}
	
	// returns the internal pointer to the beginning of the results, usefull if you need to use the same
	// results multiple times. will only work if there was > 0 results.
	public function ResetResults() {
		if ($this->getNumRows() > 0) {
			if ($this->_db_qresult->data_seek(0)) {
				$this->NextRowNumber = 0;
				$this->EOF = true;
				return true;
			}
		}
		return false;
	}
	
	// Grab number of records updated
	public function getUpdatedRecords() {
		return ($this->mysqli->affected_rows);
	}
	
	// gets a field name by it's numeric position
	public function GetFieldNameFromPos($fieldPos) {
		$fieldPos = intval($fieldPos);
		if ($fieldPos < $this->mysqli->field_count) {
			$finfo = $this->mysqli->fetch_field_direct($fieldPos);
			return $finfo->name;
		}
		return -1;
	}
	
	// returns the value of the specified field
	public function f($fieldName) {
		// if the field specified is numeric and using the default row type, look up the field name by position
		if (is_numeric($fieldName) && $this->rowArrayType === false) {
			$fieldName = $this->GetFieldNameFromPos($fieldName);
		}
		// check that the field exists, if so return the value otherwise return false
		if (isset($this->RowData[$fieldName])) {
			return $this->RowData[$fieldName];
		}
		return false;
	}
	
	public function LastInsertID() {
		return $this->mysqli->insert_id;
	}
	
	// inheriting from this one
	public function PostRead() {
		
	}
	
	// sets the type of row array fetched
	public function SetRowArrayType($arrayType) {
		$arrayType = strtolower($arrayType);
		if ($arrayType == 'assoc' || $arrayType == 'num') {
			$this->rowArrayType = $arrayType;
		} else {
			$this->rowArrayType = false;
		}
	}
	
	public function ReadRow() {
		// read a row (array type specific)
		if ($this->rowArrayType == 'num') {
			$this->RowData = $this->_db_qresult->fetch_row();
		} else {
			$this->RowData = $this->_db_qresult->fetch_assoc();
		}
		
		// if there was a row returned
		if ($this->RowData) { 
			$this->NextRowNumber++;
			$this->EOF = false;
			// run post read processing
			$this->PostRead();
			return 1;
		} else { 
			$this->EOF = true;
			return 0;
		}
	}
	
	public function GetNextRow() {
		if ($this->NextRowNumber < $this->getNumRows()) {
			// read a row (array type specific)
			if ($this->rowArrayType == 'num') {
				$nextRow = $this->_db_qresult->fetch_row;
			} else {
				$nextRow = $this->_db_qresult->fetch_assoc;
			}
			$this->_db_qresult->data_seek($this->NextRowNumber);
			return $nextRow;
		}
		return false;
	}
	
	public function EOF() {
		return ($this->EOF);
	}
	
	public function quote($str) {
		return $this->mysqli->escape_string($str);
	}
	
	public function getNumRows() {
		return $this->RowCount = (is_object($this->_db_qresult)) ? $this->_db_qresult->num_rows : 0;
	}
	
	public function RecordsReturned() {
		if ($this->getNumRows() > 0) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getNumFields() {
		$fields = $this->mysqli->field_count;
		return $fields;
	}
	
	public function obtainRowArrayCopy() {
		return $this->RowData;
	}
	
	public function dbTrans() {
		$this->mysqli->query("SET AUTOCOMMIT = 0");
		$this->mysqli->query("BEGIN");
	}
	
	
	public function queryOk() {
		return $this->mysqli->affected_rows;
	}
	
	public function dbCommit() {
		$comm = $this->mysqli->query("COMMIT");
		$this->mysqli->query("SET AUTOCOMMIT = 1");
		$this->UseGlobalTrans(false);
		return $comm;
	}
	
	public function dbRoll() {
		$this->mysqli->query("ROLLBACK");
		$this->mysqli->query("SET AUTOCOMMIT = 1");
		$this->UseGlobalTrans(false);
	}
	
	public function UseGlobalTrans($bool = true) {
		if ($bool) {
			if (isset($GLOBALS['maxGlobalTransLinkId'])) {
				$this->UseTrans();
			} else {
				$this->StartTrans();
			}
		} else {
			$this->globalTransEnabled = false;
			if (isset($GLOBALS['maxGlobalTransLinkId'])) {
				unset($GLOBALS['maxGlobalTransLinkId']);
			}
		}
	}
	
	public function UsingGlobalTrans() {
		return $this->globalTransEnabled;	
	}
	
	public function StartTrans() {
		$this->globalTransEnabled = true;
		$this->dbTrans();
		$GLOBALS['maxGlobalTransLinkId'] = $this->_db_linkid;
	}
	
	public function UseTrans() {
		$this->globalTransEnabled = true;
		$this->_db_linkid = $GLOBALS['maxGlobalTransLinkId'];
	}
	
	public function EndTrans() {
		$this->dbCommit();
	}
	public function AddTableLock($tableName, $lockType = 'WRITE') {
		if (isset($this->tableLocks[$tableName])) {
			$tableName = $tableName." AS ".$tableName.count($this->tableLocks);
		}
		$this->tableLocks[$tableName] = $lockType;
	}
	
	public function LockTables($tableName = false, $lockType = 'WRITE') {
		if ($tableName !== false) $this->AddTableLock($tableName, $lockType);
		if ($this->tableLocks) {
			$lockList = false;
			foreach ($this->tableLocks as $tableName => $lockType) {
				$lockList[] = $tableName." ".$lockType;
			}
			$temp = $this->RowData;
			$this->query("LOCK TABLE ".implode(', ', $lockList));
			$this->RowData = $temp;
		}
	}
	
	public function UnlockTables() {
		if ($this->tableLocks) {
			$temp = $this->RowData;
			$this->query("UNLOCK TABLES");
			$this->RowData = $temp;
			$this->tableLocks = false;
		}
	}
	
	public function getTabColumns() {
		$i = 0;
		$columns = array();
		if ($this->_db_qresult !== 0) {
			$fields = $this->_db_qresult->field_count;
			while ($i < $fields) {
				$finfo = $this->_db_qresult->fetch_field_direct($i);
				$type  = $finfo->type;
				$name  = $finfo->name;
				$len   = $finfo->max_length;
				$flags = $finfo->flags;
				$columns[] = $name;
				$i++;
			}
		}
		return $columns;
	}

	public function getColumns() {
		$i = 0;
		$columns = array();
		if ($this->_db_qresult !== 0) {
			$fields = $this->_db_qresult->field_count;
			while ($i < $fields) {
				$finfo = $this->_db_qresult->fetch_field_direct($i);
				$type  = $finfo->type;
				$name  = $finfo->name;
				$len   = $finfo->max_length;
				$flags = $finfo->flags;
				$columns[] = array(
							"type" => $type,
							"name" => $name,
							"len" => $len,
							"flags" => $flags);
				$i++;
			}
		}
		return $columns;
	}

	public function getTableColumns() {
		$query = $this->mysqli;
		$result = $query->query("SHOW COLUMNS IN ".$this->tableName.";");
		if ($result !==false) {
			while ($rec = $result->fetch_array()) {
				$type		= ((strpos($rec["Type"], 'int') !== false) ? 'number' : 'string');
				$name		= $rec['Field'];
				$default	= $rec['Default'];
				$null		= $rec['Null'];
				$key		= $rec['Key'];
				$columns[]	= array(
					"type"		=> $type,
					"name"		=> $name,
					"default"	=> $default,
					"null"		=> (strtolower($null)=='yes')?true:false,
					"key"		=> $key
				);
			}
		}
		return $columns;
	}
	
	public function AutoFields($defaultId = true) {
		$recs = array();
		if ($defaultId) {
			$this->SetPrimaryKey('id');
			$this->defaultOrderFields['id'] = 'ASC';
		}
		$cachefile = SYS_CACHE.'/'.md5($this->tableName);
		if (file_exists($cachefile) && !isset($_GET["nocache"])) {
			$recs = unserialize(file_get_contents($cachefile));
		} else {
			$result = $this->mysqli->query("SHOW COLUMNS IN ".$this->tableName.";");
			if ($result !==false) {
				while ($rec = $result->fetch_array()) {
					$recs[] = $rec;
				}
				file_put_contents($cachefile, serialize($recs));
			} else {
				$this->SystemError('Table "'.$this->tableName.'" does not exist, please create one.');
			}
		}
		foreach($recs as $rec) {
			$this->DefineField($rec["Field"], ((strpos($rec["Type"], 'int') !== false) ? 'number' : 'string'));
		}
	}
	
	public function Array2Date($Date) {
		return $Date['Year']."-".$Date['Month']."-".$Date['Day']." ".$Date['Hour'].":".$Date['Minute'].":".$Date['Second'];
	}
	
	public function A2D($Date) {
		return $Date['Day']."-".$Date['Month']."-".$Date['Year'];
	}
	
	public function A3D($Date) {
		return $Date['Year']."-".$Date['Month']."-".$Date['Day'];
	}
	
	public function Date2Array($Date) {
		$Date = strtotime($Date);
		return array(
				'Day' => date('d',$Date),
				'Month' => date('m',$Date),
				'Year' => date('Y',$Date),
				'Hour' => date('H',$Date),
				'Minute' => date('i',$Date),
				'Second' => date('s',$Date)
				);
	}
	
	// defines what format a field should be saved as. Can also supply the data to set as an optional parameter
	public function DefineField($fieldName, $fieldFormat, $fieldData = false) {
		$this->saveDef[$fieldName] = $fieldFormat;
		
		if ($fieldData !== false) {
			$this->SetField($fieldName, $fieldData);	
		}
	}
	
	// define a field by supplying it's mysql field type which will then be converted to the internat processing type
	public function DefineFieldByType($fieldName, $fieldType) {
		if (strpos($fieldType, 'int') !== false) {
			$this->DefineField($fieldName, 'number');
		} elseif (strpos($fieldType, 'date') !== false) {
			$this->DefineField($fieldName, 'date');
		} else {
			$this->DefineField($fieldName, 'string');
		}
	}
	
	// auto setup a table defenition based on the mysql table structure
	public function SetupTable($tableName) {
		$this->SetTableName($tableName);
		if (isset($GLOBALS['ExtDatabaseTableSetups'][$tableName])) {
			$this->saveDef = $GLOBALS['ExtDatabaseTableSetups'][$tableName]['def'];
			$this->primaryKey = $GLOBALS['ExtDatabaseTableSetups'][$tableName]['pk'];
		} else {
			$tableQuery = new database($this->currentHost, $this->currentUser, $this->currentPassword, $this->currentDb);
			$tableQuery->query("DESCRIBE `".$tableName."`");
			while ($tableQuery->read()) {
				if (strpos($tableQuery->f('Key'), 'PRI') !== false) {
					$this->SetPrimaryKey($tableQuery->f('Field'));
				}
				$this->DefineFieldByType($tableQuery->f('Field'), $tableQuery->f('Type'));
			}
			$GLOBALS['ExtDatabaseTableSetups'][$tableName] = array('def' => $this->saveDef,'pk' => $this->primaryKey);
		}
	}
	
	// returns whether a field is defined
	public function IsDefined($fieldName) {
		return isset($this->saveDef[$fieldName]);
	}
	
	// generic filter method to filter by supplied value using suplied field
	public function FilterField($fieldName, $fieldValue) {
		if ($this->IsDefined($fieldName)) {
			$this->AddWhere("AND `".$this->tableName."`.`".$fieldName."` = ".$this->FormatSaveField($fieldName, $fieldValue));
		}
	}
	
	// pass in an array and any index that matches a defined field is set in the current row data
	public function SetFieldArray($fieldArray) {
		if (is_array($fieldArray)) {
			foreach ($fieldArray as $fieldName => $fieldValue) {
				if ($this->IsDefined($fieldName)) {
					$this->SetField($fieldName, $fieldValue);
				}
			}
		}
	}
	
	// sets a field in the current data set
	public function SetField($fieldName, $fieldData, $format = false) {
		// check that for primary key fields, the value must be a valid integer above 0
		if ($fieldName != $this->primaryKey || intval($fieldData) > 0) {
			// if the data is to be saved as in a different format at runtime, update the spec
			if ($format) {
				$this->saveDef[$fieldName] = $format;
			}
					
			// store in the current data set
			$this->RowData[$fieldName] = $fieldData;
		} else if (is_null($fieldData)) {
			$this->RowData[$fieldName] = NULL;
		}
	}
	
	// Returns the data in the current data set
	public function ReturnField($fieldName)
	{
		return $this->RowData[$fieldName];
	}
	
	// sets the table name
	public function SetTableName($tableName) {
		$this->tableName = $tableName;
	}
	
	public function ReturnTableName() {
		return ($this->tableName);
	}
	
	// set the primary key
	public function SetPrimaryKey($primaryKey) {
		$this->primaryKey = $primaryKey;	
	}
	
	// Returns the last primary ID (usually run after an insert) 
	public function ObtainLastPrimaryId() {
		return $this->f($this->primaryKey);	
	}
	
	// formats a field accorting to it's type
	public function FormatSaveField($fieldName, $fieldValue = false) {
		if ($fieldValue === false && !is_null($fieldValue)) $fieldValue = $this->f($fieldName);
		// get the fields format
		$fieldFormat = $this->saveDef[$fieldName];
		// if the format is a number or public function then omit quotes, otherwise add
		if ($fieldFormat == 'number' || $fieldFormat == 'function') {
			// if value is a number, parse it
			if ($fieldFormat == 'number') {
				// if string is not numeric, remove any non numeric charachters
				if (!is_numeric($fieldValue)) {
					$fieldValue = preg_replace("[^0-9]", '', $fieldValue);
				}
				$fieldValue = floatval($fieldValue);	
			}
			// return the value without quotes
			return $fieldValue;
		} elseif ($fieldFormat == 'date') {
			return "'".$this->ToSqlDate($fieldValue)."'";
		} else {
			return "'".addslashes(stripslashes($fieldValue))."'";
		}
	}
	
	// check if a date is in mysql format
	public function IsMySqlDate($dateString) {
		if (substr($dateString, 4, 1) == '-' && substr($dateString, 7, 1) == '-') {
			return true;	
		} else {
			return false;	
		}
	}

	
	// format a date to mysql format
	public function ToSqlDate($dateString, $dateFormat = 'dd-mm-yyyy hh:ii') {
		// check if the string is already in mysql format
		if ($this->IsMySqlDate($dateString)) {
			return $dateString;
		} else {
			// lowercase the format
			$dateFormat = strtolower($dateFormat);
			// default the an array to store each element
			$dateElements = array('d' => '', 'm' => '', 'y' => '', 'h' => '', 'i' => '');
			
			// loop through each charachter in the format and if it matches a valid element, append
			for ($i = 0; $i < strlen($dateString); $i++) {
				$dateChar = substr($dateString, $i, 1);
				$formatChar = substr($dateFormat, $i, 1);
				if (isset($dateElements[$formatChar])) {
					$dateElements[$formatChar] .= $dateChar;
				}
			}
			
			// if there is a valid Y-m-d
			if (strlen($dateElements['y']) > 1 && strlen($dateElements['m']) > 0 && strlen($dateElements['d']) > 0) {
				$dateString = $dateElements['y'].'-'.$dateElements['m'].'-'.$dateElements['d'];
				// if there is a valid time
				if (strlen($dateElements['h']) > 0 && strlen($dateElements['i']) > 0) {
					$dateString .= ' '.$dateElements['h'].':'.$dateElements['i'];	
				}
				
				// return the mysql format date
				return $dateString;
			} else {
				// if invalid, return default value
				return '0000-00-00';
			}
		}
	}
	
	public function ForceNewRecord($bool = true) {
		$this->forceNewRecord = $bool;
	}
	
	public function ClearRowData() {
		$this->RowData = false;
	}
	
	public function UseAutoValues($bool = true) {
		$this->autoValues = $bool;
	}
	
	// saves the current record in the db (insert/update mode depends on primary key field being set)
	public function SaveRecord($multiRecord = false) {
		$saveResult = 0;
		// setup the row data reset status
		$resetRowData = ($multiRecord) ? false : true;
		// make sure all the parameters are setup correctly for a save
		if ($this->saveDef && $this->tableName && $this->primaryKey) {
			// default an array for data to be saved
			$saveData = false;
			// add in data fields if they are set
			foreach ($this->saveDef as $fieldName => $useQuotes) {
				if (isset($this->RowData[$fieldName])) {
					$saveData[$fieldName] = $this->FormatSaveField($fieldName);
				} else if (isset($this->RowData[$fieldName]) && is_null($this->RowData[$fieldName])) {
					$saveData[$fieldName] = 'NULL';
				}
			}
			
			// if an existing log, update otherwise insert a new one
			if (($this->f($this->primaryKey)) && ($this->forceNewRecord == false)) {
				// convert the data into update rows
				foreach ($saveData as $fieldName => $fieldData) {
					$saveData[$fieldName] = '`'.$fieldName.'` = '.$fieldData;
				}
				// store the primary key locally before the query
				$primaryKeyValue = $this->f($this->primaryKey);
				// build and run the query
				$updateQuery = "UPDATE ".$this->tableName." SET ".implode(', ', $saveData)." WHERE ".$this->primaryKey." = ".$this->f($this->primaryKey);
				$saveResult = $this->query($updateQuery, $resetRowData);
				// output debug info
				if ($this->DebugOn) {
					$this->ShowDebugText($updateQuery);
				}
				// re-store the primary key locally before the query
				$this->SetField($this->primaryKey, $primaryKeyValue);
			} else {
				// set the new record flag to true
				$this->wasNewRecord = true;
				// build an array of field names
				$fieldNames = false;
				foreach ($saveData as $fieldName => $fieldData) {
					$fieldNames[] = '`'.$fieldName.'`';
				}
				// build and run the query
				$insertQuery = "INSERT INTO ".$this->tableName." (".implode(', ', $fieldNames).")
								VALUES (".implode(', ', $saveData).")";
				$saveResult = $this->query($insertQuery, $resetRowData);
				// output debug info
				if ($this->DebugOn) {
					$this->ShowDebugText($insertQuery);
				}
				// if set to, update the primary key of the created record
				if (!$multiRecord) {
					$this->SetField($this->primaryKey, $this->LastInsertID());
				}
			}
		} else {
			$this->error("Error saving: Save parameters not setup");
		}
		
		return $saveResult;
	}
	
	// returns whether the save was a new record
	public function WasNewRecord() {
		return $this->wasNewRecord;
	}
	
	// deletes a record from the table. soft deletes by default (if avaliable)
	public function DeleteRecord($pkValue, $hardDelete = false) {
		if (isset($this->saveDef['deleted']) && !$hardDelete) {
			$RowDataCopy = $this->RowData; // preserve current row data if set
			$this->SetField($this->primaryKey, $pkValue);
			$this->SetField('deleted', 'Y');
			$this->SaveRecord();
			$this->RowData = $RowDataCopy; // restore row data
		} else {
			$this->query("DELETE FROM ".$this->tableName." WHERE ".$this->primaryKey." = ".$pkValue);
		}
	}
	
	// undeletes soft deleted records
	public function UnDeleteRecord($pkValue) {
		if (isset($this->saveDef['deleted'])) {
			$RowDataCopy = $this->RowData; // preserve current row data if set
			$this->SetField($this->primaryKey, $pkValue);
			$this->SetField('deleted', 'N');
			$this->SaveRecord();
			$this->RowData = $RowDataCopy; // restore row data
		}
	}
	
	// resets the load conditions
	public function ResetLoadConditions() {
		$this->orderFields = false;
		$this->groupFields = false;
		$this->recordLimit = false;
		$this->recordOffset = false;
		$this->whereStatements = false;
		$this->havingStatements = false;
		$this->joinStatements = false;
		$this->loadRecordsSql = false;
		$this->postSelectKeywords = false;
		$this->outputFields = false;
		$this->RowData = false;
	}
	
	// adds a field to group by
	public function AddGroupByField($fieldName, $isAlias = false) {
		// check if a table name is not specified (and is set in class), prepend the table name to the field name
		if (strpos($fieldName, '.') === FALSE && $this->tableName && !$isAlias) {
			$fieldName = $this->tableName.'.'.$fieldName;
		}
		// add to the sort fields
		$this->groupFields[] = $fieldName;
	}
	
	// build the order by statement for the query
	public function BuildGroupBy() {
		// build the group by statement
		if ($this->groupFields) {
			// return the group by statement
			return 'GROUP BY '.implode(', ', $this->groupFields);
		} else {
			// if there are no fields, return a blank string
			return '';
		}
	}
	
	// adds a field to order by
	public function AddOrderField($fieldName, $direction = 'ASC', $isAlias = false) {
		// check if a table name is not specified (and is set in class), prepend the table name to the field name
		if (strpos($fieldName, '.') === FALSE && $this->tableName && !$isAlias) {
			$fieldName = $this->tableName.'.'.$fieldName;
		}
		// add to the sort fields
		$this->orderFields[$fieldName] = $direction;
	}
	
	// orders the data randomly
	public function RandomOrder() {
		$this->AddOrderField("RAND()", "", true);	
	}
	
	// sets results paging (not compatible when using a manual limit of records (will overwrite)
	public function SetPaging($resultsPerPage, $currentPage) {
		// get the total number of records for the query
		$totalResults = $this->GetTotalRows();
		// calculate the total number of pages
		$resultsPerPage = intval($resultsPerPage);
		$this->numberOfPages = ceil($totalResults / $resultsPerPage);
		if ($this->numberOfPages > 1) {
			// perform checks on the current requested page
			$this->currentPage = intval($currentPage);
			if ($this->currentPage < 1) $this->currentPage = 1;
			if ($this->currentPage > $this->numberOfPages) $this->currentPage = $this->numberOfPages;
			// calculate the offset amount and set the appropriate results limits
			$offset = ($this->currentPage-1) * $resultsPerPage;
			$this->SetRecordLimit($resultsPerPage, $offset);
		}
	}
	
	// get values concearning paging
	public function GetCurrentPageNumber() { return $this->currentPage; }
	public function GetNumberOfPages() { return $this->numberOfPages; }
	
	// set the record limit
	public function SetRecordLimit($numberOfRecords, $recordOffset = 0) {
		$this->recordLimit = $numberOfRecords;
		$this->recordOffset = $recordOffset;
	}
	
	// build the order by statement for the query
	public function BuildOrderBy() {
		// if there is no order by fields, copy the default set in (may also be empty)
		if (!$this->orderFields) {
			$this->orderFields = $this->defaultOrderFields;	
		}
		// build the order by statement
		if ($this->orderFields) {
			$orderTemp = false;
			foreach ($this->orderFields as $fieldName => $direction) {
				$orderTemp[] = $fieldName.' '.$direction;
			}
			// return the order by statement
			return 'ORDER BY '.implode(', ', $orderTemp);
		} else {
			// if there are no fields, return a blank string
			return '';
		}	
	}
	
	// build the limit statement
	public function BuildRecordLimit() {
		if ($this->recordLimit !== false) {
			return "LIMIT ".$this->recordOffset.", ".$this->recordLimit;
		} else {
			return '';	
		}
	}
	
	// add a where statement
	public function AddWhere($whereText) {
		$this->whereStatements[] = $whereText;
	}
	
	// add a having statement
	public function AddHaving($havingText) {
		$this->havingStatements[] = $havingText;
	}
	
	// gets the current where additions
	public function BuildWhere() {
		if ($this->whereStatements) {
			return implode("\n\t", $this->whereStatements);
		} else {
			return '';	
		}
	}
	
	// gets the current having additions
	public function BuildHaving() {
		if ($this->havingStatements) {
			return "HAVING 1 = 1 ".implode("\n\t", $this->havingStatements);
		} else {
			return '';	
		}
	}
	
	// add a join to the query
	public function AddJoin($joinSql) {
		$this->joinStatements[] = $joinSql;	
	}
	
	// add a join, example: EasyJoin("inner", "t_customer_cars", "carid")
	public function EasyJoin($type, $destTable, $sourceField="", $sourceTable="", $destField="") {
		
		if ($sourceTable == "") $sourceTable = $this->tableName;
		if ($sourceField == "") $sourceField = $this->primaryKey;
		if ($destField == "") 	$destField = $sourceField;

		$join = $type." JOIN ".$destTable." on ".$sourceTable.".".$sourceField." = ".$destTable.".".$destField;
		$this->AddJoin($join);
	}
	
	// gets the current join additions
	public function BuildJoins() {
		if ($this->joinStatements) {
			return implode("\n\t", $this->joinStatements);
		} else {
			return '';	
		}
	}
	
	// adds a post select keyword
	public function AddPostSelectKeyword($keyword) {
		$this->postSelectKeywords[] = $keyword;	
	}
	
	// builds the post select keywords string
	public function BuildPostSelectKeywords() {
		if ($this->postSelectKeywords !== false) {
			return " ".implode(" ", $this->postSelectKeywords);
		}
		return '';
	}
	
	// adds an additional field to output
	public function AddOutputField($fieldName, $prepend = false) {
		if ($prepend && is_array($this->outputFields)) {
			array_unshift($this->outputFields, $fieldName);
		} else {
			$this->outputFields[] = $fieldName;	
		}
	}
	
	// adds a list of output fields
	public function AddOutputFieldList($fieldName) {
		for ($i = 0; $i < func_num_args(); $i++) {
			$this->AddOutputField(func_get_arg($i));
		}
	}
	
	// builds the list of fields to go into the query
	public function BuildOutputFields() {
		if ($this->includeAllFieldsFlag || (!$this->includeAllFieldsFlag && !$this->outputFields)) {
			$this->AddOutputField($this->tableName.".*", true);
		}
		return implode(",\n", $this->outputFields);
	}
	
	// compares two values and if they match, returns 'select'. used for html select boxes
	public function SelectCompare($compareValue, $masterValue = false) {
		// if no master value passed, assume and check the primary key field
		if ($masterValue === false && $this->primaryKey) {
			$masterValue = $this->f($this->primaryKey);
		}
		
		if ($masterValue == $compareValue) {
			return 'selected';	
		} else {
			return '';
		}
	}
	
	// checks whether the specified date field has been set
	public function DateIsSet($fieldName) {
		if (is_string($this->f($fieldName))) {
			if (substr($this->f($fieldName),0,10) != '0000-00-00') {
				return true;
			}
		}
		return false;
	}
	
	// sets the sql that will load records
	public function SetLoadSql($querySql) {
		$this->loadRecordsSql = $querySql;
	}
	
	// includes deleted records in generic query
	public function IncludeDeletedRecords() {
		$this->includeDeletedRecordsFlag = true;
	}
	
	// sets to not include all fields in the output query (this will only work if specific fields have been
	// selected to be outputted
	public function NotIncludeAllFields() {
		$this->includeAllFieldsFlag = false;
	}
	
	// sets the preference to bypass the query cache
	public function IgnoreQueryCache($bool = true) {
		$this->ignoreQueryCacheFlag = $bool;
	}
	
	// returns whether a record has been loaded by it's primary key value
	public function IsLoadedByPk() {
		return $this->filteredByPkValue;	
	}
	
	// loads a specific record based on the primary key
	public function LoadRecord($pkValue) {
		$pkValue = intval($pkValue);
		if ($this->primaryKey && $this->tableName && $pkValue > 0) {
			$this->AddWhere("AND ".$this->tableName.".".$this->primaryKey." = ".$pkValue);
			$this->filteredByPkValue = true;
			if ($this->LoadRecords()) {
				return $this->read();
			}
		}
		return false;	
	}
	
	// builds the SQL statement that is run by LoadRecords()
	public function BuildLoadRecords() {
		/*if ($this->includeDeletedRecordsFlag && isset($this->saveDef['deleted'])) {
			$this->AddWhere("AND ".$this->tableName.".deleted = ".$this->FormatSaveField('deleted', 'Y'));
		}*/
		
		// if the load records sql has not yet been built or has been reset
		if (!$this->loadRecordsSql) {
			// set the string that indicates whether the query cache will be ignored
			if ($this->ignoreQueryCacheFlag) $this->AddPostSelectKeyword('SQL_NO_CACHE');

			// build the variable that will contain the SQL
			$this->loadRecordsSql = "SELECT".$this->BuildPostSelectKeywords()." ".$this->BuildOutputFields()."
							FROM ".$this->tableName."
								".$this->BuildJoins()."
							WHERE 1 = 1
								".$this->BuildWhere()."
								".$this->BuildGroupBy()."
								".$this->BuildHaving()."
								".$this->BuildOrderBy()."
								".$this->BuildRecordLimit();
		}
		// return the current load records sql
		return $this->loadRecordsSql;
	}
	
	// loads a series of records based on table setup
	public function LoadRecords() {
		if ($this->tableName) {
			$this->query($this->BuildLoadRecords());
			return $this->RecordsReturned();
		} else {
			return false;
		}
	}
	
	// get the sql code that has just run
	public function GetLoadRecordSql() {
		return $this->loadRecordsSql;
	}
	
	// get the total number of rows without fully executing the query (ignoring limits)
	public function GetTotalRows() {
		$totalsDb = clone $this;
		$totalsDb->LoadRecords();
		return $totalsDb->getNumRows();
	}

	/* Checks to see if a record exists based on the set primary key. 
	   PLEASE NOTE: 
			1/ Wipes the current result set
			2/ Returns false on error, 0 if no record, or 1 for presence of record 
	*/
	public function Exists() {
		if ($this->primaryKey && $this->tableName) {
			$this->loadRecordsSql = "SELECT ".$this->primaryKey." from ".$this->tableName." ".
						"where ".$this->primaryKey."=".$this->ObtainLastPrimaryId()." limit 1;";
			$this->query($this->loadRecordsSql);
			return $this->RecordsReturned();
		}
		return false;
	}
	
	// gets a charachter from the field requested
	public function GetFieldChar($fieldName, $charNum = 0) {
		$charNum = intval($charNum);
		if ($charNum+1 <= strlen(strval($this->f($fieldName)))) {
			return substr(strval($this->f($fieldName)), $charNum, 1);
		}
		return '';
	}
	
	public function dateformat($date_string) {
		if (strlen($date_string) == 10) {
			$tmp_data = explode('-', $date_string);
			return $tmp_data[2] . '-' . $tmp_data[1] . '-' . $tmp_data[0];
		} else {
			return '0000-00-00';
		}
	}
	
	// gets the 'checked' value for a field
	public function CheckedValue($fieldName) {
		return (intval($this->f($fieldName)) > 0) ? 'checked' : '';	
	}

	public function pagination($recPerPage=10, $pageNum=1, $totalRecords=0, $totalPages=0, $urlPath, $col) {
		global $mainURL, $urlPagingChk;
		if ($pageNum==NULL) $pageNum=1;
		$totalPages = ceil($totalRecords / $recPerPage);
		$buildPagination = '<tfoot>';
		$buildPagination.= '<tr>';
		$buildPagination.= '<td colspan="'.$col.'" class="accounts-table__footer">';
		$buildPagination.= '	<table width="100%" cellpadding="0" cellspacing="0">';
		$buildPagination.= '	<tr>';
		$buildPagination.= '	<td width="33.33%">';
		$buildPagination.= '		<select name="filter[per_page]" class="js-ajax-form-select js-ajax-form-filter accounts-table__pages-select">';
		foreach ($this->rec_per_page as $num) {
			$sel = ($num == $recPerPage) ? ' selected="selected"' : '';
			$buildPagination.= '			<option'.$sel.'>'.$num.'</option>';
		}
		$buildPagination.= '		</select>';
		$buildPagination.= '	</td>';
		$buildPagination.= '	<td width="33.33%" align="center">';
		$buildPagination.= '		<span class="page left">'.$totalRecords.' records, '.$pageNum.' <span>of</span> '.$totalPages.' <span>Pages</span></span>';
		$buildPagination.= '	</td>';
		$buildPagination.= '	<td width="33.33%" align="right">';
		$buildPagination.= '		<input type="hidden" id="page" name="filter[page]" value="'.$pageNum.'" class="js-ajax-form-filter" />';
									$buildPagination.= '<a href="javascript:void(0);" data-update-field="page" data-update-value="'.($pageNum-1>1?$pageNum-1:($pageNum-1>0?1:1)).'" class="btn js-filter-click btn--pagination btn--prev '.($pageNum>1?'':'btn--disabled').'"></a>';
									for ($i=1; $i<$totalPages+1;$i++) {
										$buildPagination.= ($pageNum==$i)?'<span class="btn btn--pagination btn--current">'.$i.'</span>':'<a href="javascript:void(0);" data-update-field="page" data-update-value="'.$i.'" class="btn js-filter-click btn--pagination">'.$i.'</a>';
									}
									$buildPagination.= '<a href="javascript:void(0);" data-update-field="page" data-update-value="'.($pageNum+1).'" class="btn js-filter-click btn--pagination btn--next '.($totalPages>1 && $pageNum != $totalPages?'':'btn--disabled').'"></a>';
		$buildPagination.= '	</td>';
		$buildPagination.= '	</tr>';
		$buildPagination.= '	</table>';
		$buildPagination.= '</td>';
		$buildPagination.= '</tr>';
		$buildPagination.= '</tfoot>';
		return $buildPagination;
	}
}