<?php

/**
 * Generic mapper
 *
 * @uses Zend_Db
 * @package Dc_Mapper
 * @author Lysender <dc.eros@gmail.com>
 */
class Dc_Mapper_Db implements Dc_Mapper_Interface
{
	/**
	 * Database table name
	 *
	 * @var string
	 */
	protected $_table;
	
	/**
	 * Database adapter
	 *
	 * @var Zend_Db_Table
	 */
	protected $_db;
	
	/**
	 * Sets the mapper's table name
	 * 
	 * @param string $table
	 * @return $this
	 */
	public function setTable($table)
	{
		$this->_table = $table;
		
		return $this;
	}
	
	/**
	 * Returns the table name for this mapper
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->_table;
	}
	
	/**
	 * Sets db adapter
	 *
	 * @return $this
	 */
	public function setDb(Zend_Db_Adapter_Abstract $db)
	{
		$this->_db = $db;
	}
	
	public function getDb()
	{
		if ($this->_db === null)
		{
			$this->_db = self::getDefaultAdapter();
		}
		
		return $this->_db;
	}
	
	/**
	 * Returns the default db adapter from ZF
	 *
	 * @return Zend_Db_Adapter_Interface
	 */
	public static function getDefaultAdapter()
	{
		return Zend_Db_Table::getDefaultAdapter();
	}
	
	/**
	 * Gets a single record from data source
	 *
	 * @param array 	$keys
	 * @param array 	$exclude
	 * @return mixed
	 */
	public function get(array $keys, array $exclude = null)
	{
		$db = $this->getDb();
		$select = $db->select()
			->from($this->_table);
		
		foreach ($keys as $key => $value)
		{
			$select->where("$key = ?", $value);
		}
		
		if (!empty($exclude))
		{
			foreach ($exclude as $key => $value)
			{
				$select->where("$key <> ?", $value);
			}
		}
		
		return $db->fetchRow($select);
	}
	
	/**
	 * Inserts a new record to the data source
	 *
	 * @param array $data
	 * @param array options
	 * @return mixed
	 */
	public function insert(array $data, array $options = null)
	{
		$db = $this->getDb();
		
		return $db->insert($this->_table, $data);
	}
	
	/**
	 * Returns the last insert id from an auto increment field
	 *
	 * @return int
	 */
	public function lastInsertId()
	{
		return $this->getDb()->lastInsertId();
	}
	
	/**
	 * Updates a record to the data source
	 *
	 * @param array 	$key
	 * @param array 	$data
	 * @param array 	$options
	 * @return mixed
	 */
	public function update(array $keys, array $data, array $opions = null)
	{
		$db = $this->getDb();
		$where = array();
		
		foreach ($keys as $key => $value)
		{
			$where[] = $db->quoteInto("$key = ?", $value);
		}
		
		return $db->update($this->_table, $data, $where);
	}
	
	/**
	 * Deletes a record from the data source
	 *
	 * @param array 	$key
	 * @param array 	$options
	 * @return mixed
	 */
	public function delete(array $keys, array $options = null)
	{
		$db = $this->getDb();
		$where = array();
		
		foreach ($keys as $key => $value)
		{
			$where[] = $db->quoteInto("$key = ?", $value);
		}
		
		return $db->delete($this->_table, $where);
	}
	
	/**
	 * Finds a record or records from the data source
	 *
	 * @param array 	$keys
	 * @return mixed
	 */
	public function find(array $keys, array $sort = null)
	{
		$db = $this->getDb();
		$select = $db->select()
			->from($this->_table);
		
		foreach ($keys as $key => $value)
		{
			$select->where("$key = ?", $value);
		}
		
		if (!empty($sort))
		{
			$select->order($sort);
		}
		
		return $db->fetchAll($select);
	}
}