<?php

/**
* Copyright 2012, Snowfire AB, snowfireit.com
* Licensed under the MIT License.
* Redistributions of files must retain the above copyright notice.
*/

namespace Lib;

require_once 'interface.php';

class Database implements Database_Interface
{
	private $_parameters;
	
	/**
	* @var \PDO
	*/
	private $_pdo;
	
	/**
	* @param array $parameters user, pass, host, dbname
	* @return Database
	*/
	public function __construct($parameters)
	{
		$this->_parameters = array_merge(array(
			'host' => '127.0.0.1',
			'port' => 3306
		), $parameters);
	}
	
	/**
	* @param string $sql
	* @param array|string|numeric $parameters
	* @param array $option single_column
	*/
	public function one($sql, $parameters = array(), $option = null)
	{
		$stmt = $this->prepare($sql);
		$this->_stmt_parameters($stmt, $parameters);
		$this->_stmt_execute($stmt);
		
		return $stmt->fetch(
			isset($option['single_column']) && $option['single_column'] 
			? \PDO::FETCH_COLUMN 
			: \PDO::FETCH_ASSOC
		);
	}
	
	/**
	* @param string $sql
	* @param array|string|numeric $parameters
	* @param array $option single_column
	*/
	public function many($sql, $parameters = array(), $option = null)
	{
		$stmt = $this->prepare($sql);
		$this->_stmt_parameters($stmt, $parameters);
		$this->_stmt_execute($stmt);
		
		return $stmt->fetchAll(
			isset($option['single_column']) && $option['single_column'] 
			? \PDO::FETCH_COLUMN 
			: \PDO::FETCH_ASSOC
		);
	}
	
	public function execute($sql, $parameters = array())
	{
		$stmt = $this->prepare($sql);
		$this->_stmt_parameters($stmt, $parameters);
		$this->_stmt_execute($stmt);
	}
	
	public function last_insert_id()
	{
		return $this->_pdo->lastInsertId();
	}
	
	public function format_date_time($unix)
	{
		return date('Y-m-d H:i:s', $unix);
	}
	
	public function prepare($sql)
	{
		$this->_connect();
		return $this->_pdo->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
	}
	
	public function transaction_begin()
	{
		$this->_connect();
		$this->_pdo->beginTransaction();
	}
	
	public function transaction_end()
	{
		$this->_pdo->commit();
	}
	
	public function quote($value)
	{
		$this->_connect();
		return $this->_pdo->quote($value);
	}
	
	private function _stmt_parameters(&$stmt, $parameters)
	{
		$parameters = !is_array($parameters) ? array($parameters) : $parameters;
		
		// Isn't an assiciative array
		if (is_numeric(implode('', array_keys($parameters)))) {
			foreach ($parameters as $index => $value) {
				$stmt->bindValue($index + 1, $value);
			}
		} else {
			foreach ($parameters as $key => $value) {
				$stmt->bindValue(':' . $key, $value);
			}
		}
	}
	
	private function _stmt_execute(&$stmt)
	{
		if (!$stmt->execute()) {
			throw new InvalidArgumentException("Failed to execute query \"{$stmt->queryString}\"");
		}
	}
	
	private function _connect()
	{
		if (isset($this->_pdo)) {
			return;
		}
		
		$dsn = "mysql:dbname={$this->_parameters['dbname']};host={$this->_parameters['host']}";

		$this->_pdo = new \PDO(
			$dsn,
			$this->_parameters['user'],
			$this->_parameters['pass'],
			array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
		);
		
		$this->_pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	}
}