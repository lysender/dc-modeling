<?php

/**
 * Handle models that are used in subforms ex: multiple rows for models
 *
 * @package Dc_Model
 * @author Lysender <dc.eros@gmail.com>
 */
class Dc_Model_Group
{
	/**
	 * @var Dc_Model_Abstract
	 */
	protected $_model;
	
	/**
	 * Data for models
	 *
	 * Format: array(
	 *		0	=> array(field1 => data1, field2 => data2, ...),
	 *		1	=> array(...),
	 *		...
	 * )
	 *
	 * @var array
	 */
	protected $_data = array();
	
	/**
	 * Original fields for the model
	 *
	 * @var array
	 */
	protected $_originalFields = array();
	
	/**
	 * @var int
	 */
	protected $_modelCount = 0;
	
	/**
	 * The currently initialized view helper model index
	 * 
	 * @var int
	 */
	protected $_initViewHelperIndex;
	
	/**
	 * The index of the currently loaded values for the model
	 *
	 * @var int
	 */
	protected $_initValueIndex;
	
	/**
	 * Name suffix for field name and ids
	 *
	 * @var string
	 */
	public $nameSuffix;
	
	/**
	 * Factory method is forced
	 */
	protected function __construct(Dc_Model_Abstract $model, $count, array $options = null)
	{
		$this->setModel($model);
		$this->_modelCount = (int)$count;
		
		$this->_originalFields = $this->getModel()->getFields();
	}
	
	/**
	 * Factory method
	 *
	 * @param Dc_Model_Abstract $model
	 * @param int $count
	 * @param array $options
	 * @return Dc_Model_Group
	 */
	public static function factory(Dc_Model_Abstract $model, $count, array $options = null)
	{
		return new self($model, $count, $options);
	}
	
	/**
	 * Sets the model
	 *
	 * @param Dc_Model_Abstract
	 * @return $this
	 */
	public function setModel(Dc_Model_Abstract $model)
	{
		$this->_model = $model;
		return $this;
	}
	
	/**
	 * Returns the model
	 *
	 * @return Dc_Model_Abstract
	 */
	public function getModel()
	{
		return $this->_model;
	}
	
	/**
	 * Returns the model count
	 *
	 * @return int
	 */
	public function getCount()
	{
		return $this->_modelCount;
	}
	
	/**
	 * Returns the original / unmodified fields for the model
	 *
	 * @return array
	 */
	public function getOriginalFields()
	{
		return $this->_originalFields;
	}
	
	/**
	 * Sets the original fields
	 *
	 * @param array $fields
	 * @return $this
	 */
	public function setOriginalFields(array $fields)
	{
		$this->_originalFields = $fields;
		
		return $this;
	}
	
	/**
	 * Initialize view helpers so that field names are displayed
	 * according to array notation names for valid XHTML
	 *
	 * @param int $index
	 * @param string $nameSuffix
	 * @return void
	 */
	public function initViewHelpers($index)
	{
		// initialize fields
		$this->_initViewHelperIndex = $index;
		
		$fields = $this->_originalFields;
		$newFields = array();
		foreach ($fields as $field => $options)
		{
			// initialize view helper attributes
			if (isset($options['viewHelpers']))
			{
				foreach ($options['viewHelpers'] as $viewHelper => $params)
				{
					// set name
					$options['viewHelpers'][$viewHelper]['name'] = $field . $this->nameSuffix . '[]';
					// set id
					$options['viewHelpers'][$viewHelper]['attribs']['id'] = $field . $this->nameSuffix . '_' . $index;
				}
			}
			$newFields[$field] = $options;
		}
		
		unset($fields);
		$this->getModel()->setFields($newFields);
	}
	
	/**
	 * Renders a view helper for the specified field
	 *
	 * @return string
	 */
	public function view($index, $field, $viewHelper, array $attribs = null)
	{
		$index = (int)$index;
		if ($this->_initViewHelperIndex !== $index)
		{
			$this->initViewHelpers($index);
			$this->initValues($index);
		}
		
		return $this->getModel()->view($field, $viewHelper, $attribs);
	}
	
	/**
	 * Sets the values for the group
	 *
	 * Expects: array(
	 *		'field1' => array(
	 *			0 => value1,
	 *			1 => value2,
	 *			...
	 * 		),
	 * 		'field2' => array(
	 *			0 => value1,
	 *			1 => value2
	 * 		),
	 * 		...
	 * )
	 *
	 * Sets data into: array(
	 *		0 => array(
	 *			'field1' => value1,
	 *			'field2' => value2,
	 *			...
	 * 		),
	 * 		1 => array(
	 *			'field1' => value1,
	 *			'field2' => value2
	 * 		),
	 * 		...
	 * 	)
	 *
	 * @param array $data
	 * @return $this
	 */
	public function setValues(array $data)
	{
		$model = $this->getModel();
		$fields = $model->getFields();
		$fields = array_keys($fields);
		
		$max = $this->getCount();
		$this->_data = array();
		
		// load values from raw data
		foreach ($fields as $field)
		{
			// iterates through the field array values
			for ($x = 0; $x < $max; $x++)
			{
				// verify if it exists on raw data
				if (isset($data[$field][$x]))
				{
					// set our data format
					$this->_data[$x][$field] = $data[$field][$x];
				}
			}
		}
		
		// remove empty fields from data
		foreach ($this->_data as $index => $group)
		{
			$this->_data[$index] = $model->removeEmptyKeys($group);
		}
		
		// finish
		return $this;
	}
	
	/**
	 * Sets the value from a well formed format source
	 *
	 * Expects: array(
	 *		0 => array(
	 *			'field1' => value1',
	 *			...
	 * 		),
	 * 		...
	 * )
	 *
	 * @param array $data
	 * @return $this
	 */
	public function setIndexedValues(array $data)
	{
		$this->_data = array();
		
		$fields = $this->getModel()->getFields();
		foreach ($data as $key => $row)
		{
			foreach ($row as $field => $value)
			{
				if (isset($fields[$field]))
				{
					$this->_data[$key][$field] = $value;
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * Returns the grouped values
	 *
	 * @return array
	 */
	public function getValues()
	{
		return $this->_data;
	}
	
	/**
	 * Sets a group of values via index to the model
	 *
	 * @param int $index
	 * @return void
	 */
	public function initValues($index)
	{	
		$this->_initValueIndex = $index;
		$this->getModel()->clearValues();
		
		if (isset($this->_data[$index]))
		{
			$this->getModel()->setValues($this->_data[$index]);
		}
	}
	
	/**
	 * Saves current model values back to indexed data for the group
	 *
	 * @param int $index
	 * @return $this
	 */
	public function saveCurrentValues($index)
	{
		$this->_data[$index] = $this->getModel()->getValues();
		
		return $this;
	}
	
	/**
	 * Modifies the indexes of error messages to comply to the group naming
	 * on views. Used when need to focus on element using the field name
	 * plus the index
	 *
	 * @param array $messages
	 * @return array
	 */
	public function reIndexMessages($index, array $messages)
	{
		$ret = array();
		foreach ($messages as $key => $message)
		{
			$ret[$key . '_' . $index] = $message;
		}
		
		return $ret;
	}
}