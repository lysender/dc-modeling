<?php

/**
 * Interface class for Dc_Mapper compatible data mapper objects
 *
 * @package 	Dc_Mapper
 * @author		Lysender <dc.eros@gmail.com>
 */
interface Dc_Mapper_Interface
{
	/**
	 * Gets a single record from data source
	 *
	 * @param array 	$keys
	 * @param array 	$exclude
	 * @return mixed
	 */
	public function get(array $keys, array $exclude = null);
	
	/**
	 * Inserts a new record to the data source
	 *
	 * @param array 	$data
	 * @param array 	options
	 * @return mixed
	 */
	public function insert(array $data, array $options = null);
	
	/**
	 * Updates a record to the data source
	 *
	 * @param array 	$key
	 * @param array 	$data
	 * @param array 	$options
	 * @return mixed
	 */
	public function update(array $keys, array $data, array $opions = null);
	
	/**
	 * Deletes a record from the data source
	 *
	 * @param array 	$key
	 * @param array 	$options
	 * @return mixed
	 */
	public function delete(array $keys, array $options = null);
	
	/**
	 * Finds a record or records from the data source
	 *
	 * @param array 	$keys
	 * @param array		$sort
	 * @return mixed
	 */
	public function find(array $keys, array $sort = null);
}
