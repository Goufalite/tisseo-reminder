<?php

class BindParameter {
	function __construct($type,&$var)
	{
		$this->type = $type;
		$this->var = &$var;
	}
	
	public $type;
	public $var;
}

interface Connector {
	public function rawQuery($str);
	public function execQuery($str);
	public function isSuccess();
	public function getRow();
	public function getResult();
	public function getNumRows();
	
	public function prepare($str);
	// each language has its own binding mechanism
	public function getStatement();
	public function bind($params);
	public function bindOneValue($pos,&$ref,$type);
	public function execute();
	
	public function getConnectionError();
	public function getLastError();
	public function getConnector();
}

class MySQLConnector implements Connector {
	private $connector;
	private $lastResult;
	private $stmt;
	
	// return true on error
	public function connect($host,$login,$pass,$db)
	{
		$this->connector = @new mysqli($host,$login,$pass,$db);
		return $this->connector->connect_error;
	}
	
	public function getConnectionError()
	{
		return $this->connector->connect_error;
	}
	
	public function rawQuery($str)
	{
		$this->lastResult = @$this->connector->query($str);
	}
	
	public function execQuery($str)
	{
		$this->rawQuery($str);
	}
	
	public function isSuccess() 
	{
		// don't pass the result
		return $this->lastResult?true:false;
	}
	
	public function getRow()
	{
		return @$this->lastResult->fetch_assoc();
	}
	
	public function getResult()
	{
		return $this->lastResult;
	}
	
	public function getNumRows()
	{
		return @$this->lastResult->num_rows;
	}
	
	public function getLastError()
	{
		return $this->connector->error;
	}
	
	public function getConnector()
	{
		return $this->connector;
	}
	
	public function prepare($str)
	{
		$this->stmt = $this->connector->prepare($str);
		return $this->stmt?true:false;
	}
	
	public function getStatement()
	{
		return $this->stmt;
	}
	
	private function toType($type)
	{
		$t = "s";
		switch($type)
		{
			case "string":
				$t = "s";
				break;
			case "integer":
				$t = "i";
				break;
			case "float":
			case "double":
				$t = "d";
				break;
		}
		return $t;
	}
	
	public function bind($params)
	{
		$t = "";
		$tab = array();
		foreach ($params as $v)
		{
			$t .= $this->toType($v->type);
		}
		$tab[] = & $t;
		foreach ($params as $v)
		{
			$tab[] = &$v->var;
		}
		call_user_func_array(array($this->stmt,"bind_param"),$tab);
	}
	
	public function bindOneValue($pos,&$ref,$type)
	{
		$t = $this->toType($type);
		$this->stmt->bind_param($t,$ref);
	}
	
	public function execute()
	{
		$out = $this->stmt->execute();
		$this->lastResult = $this->stmt->get_result();
		return $out;
	}
}

class SQLiteConnector implements Connector {
	private $connector;
	private $stmt;
	private $lastResult;
	private $connectionError;
	private $lastError;
	
	private $warnerror;
	
	// return true on error
	public function connect($file, $mode)
	{
		//SQLITE3_OPEN_READWRITE
		try {
			$this->connector = @new SQLite3($file,$mode);
		}
		catch(Exception $e)
		{
			$this->connectionError = $e->getMessage();
			return true;
		}
	}
	
	public function getConnectionError()
	{
		return $this->connectionError;
	}
	
	public function rawQuery($str)
	{
		$this->lastResult = @$this->connector->query($str);
		$this->lastError = $this->connector->lastErrorMsg();
	}
	
	public function execQuery($str)
	{
		$this->lastResult = @$this->connector->exec($str);
		$this->lastError = $this->connector->lastErrorMsg();
	}
	
	public function isSuccess() 
	{
		// don't pass the result
		return $this->lastResult?true:false;
	}
	
	public function getRow()
	{
		return $this->lastResult->fetchArray();
	}
	
	public function getResult()
	{
		return $this->lastResult;
	}
	
	public function getNumRows()
	{
		// no num rows in sqlite3
		$cpt = 0;
		while($this->lastResult->fetchArray())
		{
			$cpt++;
		}
		$this->lastResult->reset();
		return $cpt;
	}
	
	public function getLastError()
	{
		return $this->lastError;
	}
	
	public function getConnector()
	{
		return $this->connector;
	}
	
	public function prepare($str)
	{
		$this->stmt = @$this->connector->prepare($str);
		$this->lastError = $this->connector->lastErrorMsg();
		return $this->stmt?true:false;
	}
	
	public function getStatement()
	{
		return $this->stmt;
	}
	
	private function toType($type)
	{
		$t = SQLITE3_TEXT;
		switch($type)
		{
			case "string":
				$t = SQLITE3_TEXT;
				break;
			case "integer":
				$t = SQLITE3_INTEGER;
				break;
			case "float":
				$t = SQLITE3_FLOAT;
				break;
			case "null":
				$t = SQLITE3_NULL;
				break;
		}
		return $t;
	}
	
	public function bind($params)
	{
		$cpt = 1;
		foreach($params as $v)
		{
			$this->stmt->bindParam($cpt,$v->var,$this->toType($v->type));
			$cpt++;
		}
	}
	
	public function bindOneValue($pos,&$ref,$type)
	{
		$t = $this->toType($type);
		$this->stmt->bindParam($pos,$ref,$t);
	}
	
	public function execute()
	{
		set_error_handler(array($this,"warning_handle"),E_WARNING);
		$this->lastResult = $this->stmt->execute();
		return $this->lastResult;
		restore_error_handler();
	}
	
	// pff bad execute is a warning
	public function warning_handle($errno, $errstr)
	{
		$this->lastError = $errstr;
	}
}
?>