<?php

class Library_Database extends PDO {
	
	
	function __construct() {
		try {
			parent::__construct('mysql:host=' . DB_HOST . ";dbname=" . DB_NAME . ';charset=UTF-8', DB_USER, DB_PASSWORD);
		} catch (PDOException $e) {
			echo $e->getMessage();
			die();
		}
		parent::setAttribute(parent::ATTR_ERRMODE, parent::ERRMODE_WARNING );  
	}
	
	
	private function write2Log($message) {
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/sql.txt', sprintf("[%s] %s\r", date('Y-m-d H:i:s'), $message), FILE_APPEND);
	}
	
	
	public function fetchObj($stmt) {
		$stmt->setFetchMode(PDO::FETCH_OBJ);
		$result = array();
		while ($row = $stmt->fetch()) {
			$result[] = $row;
		}
		return $result;
	}
	
	
	public function query($sql) {
		$this->dumpRawQuery($sql, array(), true);
		return parent::query($sql);
	}


	public function insertQuery($table, $params, $noPrepareParams = array()) {
		if (!is_array($params) || !count($params)) return false;
		
		$noPrepareParams['createdAt'] = 'NOW()';
		$noPrepareParams['updatedAt'] = 'NOW()';
		
		$values = array();
		foreach ($params as $k => $v) {
			$values[':' . $k] = $v;
		}
		
		$fields = '`' . implode('`, `', array_keys($params)) . '`';
		$placeholder = implode(', ', array_keys($values));
		
		if(count($noPrepareParams) != 0) {
			$placeholder .=  ", " . implode(", ", array_values($noPrepareParams)) . "";
			$fields .= ', `' . implode('`, `', array_keys($noPrepareParams)) . '`';
		}
		
		$sql  = 'INSERT INTO `' . $table . '` (' . $fields . ') VALUES (' . $placeholder . ')';
		
		try {
			$stmt = parent::prepare($sql);
		} catch (PDOException $exc) {
			echo $exc->getMessage();
			$this->dumpRawQuery($sql, $params, true);
			die();
		}
		try {
			$stmt = $stmt->execute($params);
		} catch (PDOException $exc) {
			echo $exc->getMessage();
			$this->dumpRawQuery($sql, $params, true);
			die();
		}
		return $stmt;
	}
	
	
	private function dumpRawQuery($query, $params, $log = false) {
		foreach($params as $key => $value) {
			$query = str_replace(":$key", $value, $query);
		}

		if($log === true)
			$this->logMessage($query);

		return $query;
	}
}
?>