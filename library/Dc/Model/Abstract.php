<?php

/**
 * Dc Modeling library
 *
 * @package 	Dc_Model
 * @author 		Lysender <dc.eros@gmail.com>
 */
abstract class Dc_Model_Abstract
{
	const VALIDATOR_PREFIX 		= 'Zend_Validate_';
	const FILTER_PREFIX 		= 'Zend_Filter_';
	const VIEW_HELPER_PREFIX 	= 'Zend_View_Helper_';
	
	const TYPE_VALIDATOR 		= 'validator';
	const TYPE_FILTER 			= 'filter';
	const TYPE_VIEWHELPER 		= 'viewHelper';
	
	protected $_objectTypes 	= array(
		self::TYPE_VALIDATOR		=> self::VALIDATOR_PREFIX,
		self::TYPE_FILTER			=> self::FILTER_PREFIX,
		self::TYPE_VIEWHELPER		=> self::VIEW_HELPER_PREFIX
	);
	
	/**
	 * @var Zend_Session_Namespace
	 */
	protected $_session;
	
	/**
	 * @var string
	 */
	protected $_salt = '8bffe9b33e83cdbaa4';
	
	/**
	 * Namespace used for model and mappers
	 *
	 * @var string
	 */
	protected $_namespace;
	
	/**
	 * Model name
	 *
	 * @var string
	 */
	protected $_name;
	
	/**
	 * Model fields - must be the same all the way from form, database table, etc
	 *
	 * When field is set as required, NotEmpty validator is automatically insert
	 * if it does not exists. If it is not required and the value is empty,
	 * validations are skipped
	 *
	 * When a field is set to nullWhenEmpty, if the value for the field is empty,
	 * it is converted to null. This is usefull for fields that does not
	 * accepts empty string values such as int, floats, dates, etc
	 *
	 * Validator class name is first assumed a Zend_Validate type ex:
	 * 		Alnum = is assumed as Zend_Validate_ + Alnum
	 * Otherwise, the name is assumed as whole name for example those
	 * custom validators
	 *
	 * View helper name must be lower case first and all view helpers must
	 * conform to Zend_View_Helper standards
	 * 
	 * Format: array(
	 * 		'field1' => array(
	 * 			'required' 		=> true/false,
	 * 			'nullWhenEmpty' => true/false,
	 * 			'dependent' 	=> 'other field name, where validation is not run when the field it depends on has errors'
	 * 			'filters' => array(
	 *				'Filter_Class_Name' => array(
	 *					'options'			=> array(
	 *						'filterOptionKey'	=> 'optionValue',
	 *						...
	 *					),
	 *					... future options
	 *				)
	 * 			),
	 *			'validators' => array(
	 *				'Validator_Class_Name' => array(
	 *					'breakChainOnFailure'	=> true/false,
	 *					'options'				=> array(
	 *						'validatorOptionKey'	=> 'optionValue',
	 *						...
	 * 					)
	 * 				),
	 * 				...
	 * 			),
	 * 			'viewHelpers' => array(
	 *				'viewHelperName' => array(
	 *					'name' 		=> optional name defaults field name,
	 *					'value' 	=> optional value defaults null,
	 *					'attribs'	=> optional array(attribute => value, ...),
	 *					'choices'	=> optional array(value => label, ...)
	 * 				),
	 * 				...
	 * 			)
	 * 		)
	 * 		'field2',
	 * 		'field3',
	 * 		'field4'
	 * 		...
	 * )
	 * 
	 * @var array
	 */
	protected $_fields = array();
	
	/**
	 * Fields that are on the model but is not mapped on the data source
	 *
	 * @var array
	 */
	protected $_notMapped = array();
	
	/**
	 * Values for each field of this model
	 *
	 * Format: array(
	 *		'field1' => 'value1',
	 *		'field2' => 'value2',
	 *		...
	 * )
	 *
	 * @var array
	 */
	protected $_values = array();
	
	/**
	 * Original values from the data source
	 *
	 * @var array
	 */
	protected $_originalValues = array();
	
	/**
	 * Data mapper - any object that maps to data access
	 *
	 * @var Dc_Mapper_Interface
	 */
	protected $_mapper;
	
	/**
	 * Validator messages for defined validators
	 *
	 * Validator class name must be the same as the key defined
	 * at _validators
	 *
	 * Format: array(
	 *		'field' => array(
	 *			'Validator_Class_Name' => array(
	 *				'Validator_Error_Type1' => 'Message template1',
	 *				'Validator_Error_Type2' => 'Message template2',
	 *				...
	 * 			),
	 * 			...
	 *		),
	 *		...
	 * )
	 *
	 * @var array
	 */
	protected $_validatorMessages = array();
	
	/**
	 * Field messages
	 *
	 * @var array
	 */
	protected $_messages = array();
	
	/**
	 * Path to validator message config
	 * 
	 * @var string
	 */
	protected $_validatorMessagesConfig;
	
	/**
	 * Indicates that th view helpers are initilized or not
	 *
	 * @var boolean
	 */
	protected $_isViewHelperInit = false;
	
	/**
	 * Contains plugin objects such as validators, filters and view helpers.
	 * Each objects are used if they are used by multiple fields
	 *
	 * Format: array(
	 * 		'type'	=> array(
	 *			'Full_Class_Name' => $obj,
	 *			...
	 *		),
	 *		...
	 * )
	 *
	 * @var array
	 */
	protected static $_pluginCache = array();
	
	/**
	 * Initialize the model
	 *
	 * @param array $options
	 * @return void
	 */
	public function __construct(array $options = array())
	{
		// mapper
		if (isset($options['mapper']))
		{
			$this->setMapper($options['mapper']);
		}
		
		$this->init();
	}
	
	/**
	 * Getter for fields
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->getValue($name);
	}
	
	/**
	 * Setter for fields
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function __set($name, $value)
	{
		return $this->setValue($name, $value);
	}
	
	/**
	 * Returns the object as an array using currently loaded values
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->getValues();	
	}
	
	/**
	 * Returns true if and only if the value is considered empty
	 *
	 * @return boolean
	 */
	public function isEmpty($value)
	{
		if ($value !== 0 && $value !== '0' && trim($value) == '' && empty($value))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Returns true if the model is empty
	 * Empty model where all fiels has empty values
	 *
	 * @return boolean
	 */
	public function isModelEmpty()
	{
		$values = $this->getValues();
		$values = $this->removeEmptyKeys($values);
		
		return empty($values);
	}
	
	/**
	 * Remove empty keys from the key array
	 *
	 * @return array
	 */
	public function removeEmptyKeys(array $keys)
	{
		foreach ($keys as $key => $value)
		{
			//number ZERO is not empty
			if ($this->isEmpty($value))
			{
				unset($keys[$key]);
			}
		}
		
		return $keys;
	}
	
	/**
	 * Loads the data from the source into the current object
	 * Only mapped fields are loaded
	 *
	 * @param array $keys
	 * @return $this
	 */
	public function load(array $keys = null)
	{
		if ($keys === null)
		{
			$keys = $this->getMappedValues();
		}
		
		// check if all keys are not empty
		$keys = $this->removeEmptyKeys($keys);
		if (empty($keys))
		{
			return $this;
		}
		
		$mapper = $this->getMapper();
		$data = $mapper->get($keys);
		if (!empty($data))
		{
			$this->setValues($data);
			$this->_originalValues = $data;
		}
		
		return $this;
	}
	
	/**
	 * Returns true if the model is loaded with data from source
	 *
	 * @return boolean
	 */
	public function isLoaded()
	{
		return !empty($this->_originalValues);
	}
	
	/**
	 * Resets all data
	 *
	 * @return $this
	 */
	public function reset()
	{
		$this->_values = array();
		$this->_originalValues = array();
		
		return $this;
	}
	
	/**
	 * Set session
	 *
	 * @param Zend_Session_Namespace $session
	 * @return $this
	 */
	public function setSession(Zend_Session_Namespace $session)
	{
		$this->_session = $session;
	}
	
	/**
	 * Returns session
	 *
	 * @return Zend_Session_Namespace
	 */
	public function getSession()
	{
		if ($this->_session === null)
		{
			$this->_session = new Zend_Session_Namespace($this->getSessionNamePrefix());
		}
		return $this->_session;
	}
	
	/**
	 * Returns session name for this model
	 *
	 * @return string
	 */
	public function getSessionNamePrefix()
	{
		return __CLASS__ . $this->_salt . $this->_name;
	}
	
	/**
	 * Gets the sesion value for a field (if it is stored in session)
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return $$this
	 */
	public function setSessionValue($field, $value)
	{
		$session = $this->getSession();
		$session->$field = $value;
		return $this;
	}
	
	/**
	 * Returns the value of a field stored on session
	 *
	 * @return mixed
	 */
	public function getSessionValue($field)
	{
		$session = $this->getSession();
		return $session->$field;
	}
	
	/**
	 * Abstract init function to initialize fields
	 * Based on Kohana's Sprig
	 * 
	 * @return void
	 */
	abstract public function init();
	
	/**
	 * Set the fields
	 *
	 * @param array $fields
	 * @return $this
	 */
	public function setFields(array $fields)
	{
		$this->_fields = $fields;
		
		return $this;
	}
	
	/**
	 * Returns the fields
	 *
	 * @return array
	 */
	public function getFields()
	{
		return $this->_fields;
	}
	
	/**
	 * Sets a single field
	 *
	 * @param string $field
	 * @param array $options
	 * @return $this
	 */
	public function setField($field, $options)
	{
		$this->_fields[$field] = $options;
	}
	
	/**
	 * Returns the field options
	 *
	 * @param string $field
	 * @return array
	 */
	public function getField($field)
	{
		if (array_key_exists($field, $this->_fields))
		{
			return $this->_fields[$field];
		}
		
		return null;
	}
	
	/**
	 * Returns true if the field exists
	 *
	 * @param string $field
	 * @return boolean
	 */
	public function hasField($field)
	{
		return array_key_exists($field, $this->_fields);
	}
	
	/**
	 * Sets the mapper for this model
	 *
	 * @param Dc_Mapper_Interface
	 * @return $this
	 */
	public function setMapper(Dc_Mapper_Interface $mapper)
	{
		$this->_mapper = $mapper;
		
		return $this;
	}
	
	/**
	 * Returns the mapper for this model
	 *
	 * @return Dc_Mapper_Interface
	 */
	public function getMapper()
	{
		if ($this->_mapper === null)
		{
			$this->_mapper = $this->getDefaultMapper();
		}
		
		return $this->_mapper;
	}
	
	/**
	 * Returns true if the request record via keys exists on the data source
	 *
	 * @param array $keys
	 * @return boolean
	 */
	public function recordExists(array $keys)
	{
		$keys = $this->removeEmptyKeys($keys);
		$mapper = $this->getMapper();
		$result = $mapper->get($keys);
		
		if (!empty($result))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Returns the default data mapper for this model
	 *
	 * @return Dc_Mapper_Interface
	 * @throws Dc_Model_Exception
	 */
	public function getDefaultMapper()
	{
		$className = $this->_namespace . 'Model_' . ucfirst($this->_name) . 'Mapper';
		if (!class_exists($className))
		{
			throw new Dc_Model_Exception("Class $className does not exists");
		}
		
		return new $className;
	}
	
	/**
	 * Sets a validator name for a field
	 * 
	 * @param string $field
	 * @param string $validatorName
	 * @param array $options
	 * @return $this
	 * @throws Dc_Model_Exception
	 */
	public function setValidator($field, $validatorName, array $options = array())
	{
		if (!array_key_exists($field, $this->_fields))
		{
			throw new Dc_Model_Exception("Field $field does not exists");
		}
		
		$this->_fields[$field]['validators'][$validatorName] = $options;
		
		return $this;
	}
	
	/**
	 * Set validator names for a single field
	 *
	 * @param string $field
	 * @param array $validators
	 * @return $this
	 * @throws Dc_Model_Exception
	 */
	public function setValidators($field, array $validators)
	{
		if (!array_key_exists($field, $this->_fields))
		{
			throw new Dc_Model_Exception("Field $field does not exists");
		}
		
		foreach ($validators as $validatorName => $options)
		{
			$this->setValidator($field, $validatorName, $options);
		}
		
		return $this;
	}
	
	/**
	 * Return validators for this model
	 *
	 * @return array
	 */
	public function getValidators()
	{
		$vald = array();
		foreach ($this->_fields as $f => $options)
		{
			if (isset($options['validators']))
			{
				$vald[$f] = $options['validators'];
			}
		}
		return $vald;
	}
	
	/**
	 * Sets a validator message
	 *
	 * The messages key are the validator error type and the
	 * messages values are the messages
	 * 
	 * @param string $field
	 * @param string $validatorName
	 * @param string $errorType
	 * @param string $message
	 * @return $this
	 * @throws Dc_Model_Exception
	 */
	public function setValidatorMessage($field, $validatorName, $errorType, $message)
	{
		if (!in_array($field, $this->_fields))
		{
			throw new Dc_Model_Exception("Field $field does not exists");
		}
		
		$this->_validatorMessages[$field][$validatorName][$errorType] = $message;
		
		return $this;
	}
	
	/**
	 * Set validator messages
	 *
	 * The message keys are the error types and the values are the messages
	 *
	 * @param string $field
	 * @param string $validatorName
	 * @param array $messages
	 * @return $this
	 * @throws Dc_Model_Exception
	 */
	public function setValidatorMessages($field, $validatorName, array $messages)
	{
		if (!in_array($field, $this->_fields))
		{
			throw new Dc_Model_Exception("Field $field does not exists");
		}
		
		foreach ($messages as $errorType => $message)
		{
			$this->setValidatorMessage($field, $validatorName, $errorType, $message);
		}
		
		return $this;
	}
	
	/**
	 * Returns all validator messages
	 *
	 * @return array
	 */
	public function getValidatorMessages()
	{
		return $this->_validatorMessages;
	}
	
	/**
	 * Sets a view helper
	 * 
	 * @param string $field
	 * @param string $viewHelperName
	 * @param array $options
	 * @return $this
	 * @throws Dc_Model_Exception
	 */
	public function setViewHelper($field, $viewHelperName, array $options = array())
	{
		if (!in_array($field, $this->_fields))
		{
			throw new Dc_Model_Exception("Field $field does not exists");
		}
		
		$this->_fields[$field][$viewHelperName] = $options;
		return $this;
	}
	
	/**
	 * Set view helpers
	 *
	 * @param string $field
	 * @param array $viewHelpers
	 * @return $this
	 * @throws Dc_Model_Exception
	 */
	public function setViewHelpers($field, array $viewHelpers)
	{
		if (!in_array($field, $this->_fields))
		{
			throw new Dc_Model_Exception("Field $field does not exists");
		}
		
		foreach ($viewHelpers as $viewHelperName => $options)
		{
			$this->setViewHelper($field, $viewHelperName, $options);
		}
		
		return $this;
	}
	
	/**
	 * Returns all validator messages
	 *
	 * @return array
	 */
	public function getViewHelpers()
	{
		return $this->_viewHelpers;
	}
	
	/**
	 * Sets values for existing fields
	 *
	 * @param array $values
	 * @return $this
	 */
	public function setValues(array $values)
	{
		foreach ($values as $field => $value)
		{
			$this->setValue($field, $value);
		}
		
		return $this;
	}
	
	/**
	 * Sets value for a field
	 *
	 * @param string 	$field
	 * @param mixed 	$value
	 */
	public function setValue($field, $value)
	{
		if (array_key_exists($field, $this->_fields))
		{
			$this->_values[$field] = $value;
		}
		
		return $this;
	}
	
	/**
	 * Returns value for a given field
	 *
	 * @param string 	$field
	 * @return mixed
	 * @throws Exception
	 */
	public function getValue($field)
	{
		if (array_key_exists($field, $this->_fields))
		{
			if (array_key_exists($field, $this->_values))
			{
				return $this->_values[$field];
			}
		}
		
		return null;
	}
	
	/**
	 * Returns values for fields
	 * Only returns field values that has been manually set
	 *
	 * @return array
	 */
	public function getValues()
	{
		return $this->_values;
	}
	
	/**
	 * Removes all values (unset)
	 *
	 * @return $this
	 */
	public function clearValues()
	{
		$this->_values = array();
		return $this;
	}
	
	/**
	 * Returns all fields that are changed along with its values
	 *
	 * @return array
	 */
	public function getChangedValues()
	{
		// if original values is not set, all values are assumed changed
		if (empty($this->_originalValues))
		{
			return $this->getValues();
		}
		
		// otherwise, compare current values to original
		$changed = array();
		$current = $this->getValues();
		$original = $this->_originalValues;
		
		foreach ($current as $field => $value)
		{
			// only mapped fields are processed here
			if (array_key_exists($field, $original))
			{
				if ($original[$field] != $value)
				{
					$changed[$field] = $value;
				}
			}
		}
		
		return $changed;
	}
	
	/**
	 * Returns the original values for this model
	 *
	 * Use only when the model is loaded
	 *
	 * @return array
	 */
	public function getOriginalValues()
	{
		return $this->_originalValues;
	}
	
	/**
	 * Returns all values that are set and are mapped to data source
	 *
	 * @return array
	 */
	public function getMappedValues()
	{
		$values = $this->getValues();
		foreach ($this->_notMapped as $key)
		{
			if (array_key_exists($key, $values))
			{
				unset($values[$key]);
			}
		}
		
		return $values;
	}
	
	/**
	 * Returns the base name for a requested clas
	 *
	 * Base name is the short name for a class ex:
	 * 		Zend_Validate_Alnum = Alnum
	 * 		for type validator
	 *
	 * @param string 	$class
	 * @param string 	$type
	 * @return string
	 */
	public function getClassBaseName($class, $type)
	{
		$prefix = $this->_objectTypes[$type];
		$find = strpos($class, $prefix);
		
		if ($find  === false)
		{
			// return the class itself since it is not a short name
			return $class;
		}
		
		return substr($class, $find, strlen($class) - $find);
	}
	
	/**
	 * Returns the class' full name based on its type
	 *
	 * $class maybe a short name ex:
	 * 		Alnum for validator type = Zend_Validate_Alnum
	 *
	 * If no full name is found, original class is returned, however, if
	 * the original class does not exists, null is returned
	 *
	 * @param string $class
	 * @param string $type
	 * @return string
	 */
	public function getClassFullName($class, $type)
	{
		if (class_exists($class, true))
		{
			return $class;
		}
		
		$prefix = $this->_objectTypes[$type];
		$fullname = $prefix . ucfirst($class);
		
		if (class_exists($fullname, true))
		{
			return $fullname;
		}
		
		return null;
	}
	
	/**
	 * Intialize view helpers
	 * Implemented by child classes
	 *
	 * @return $this
	 */
	public function initViewHelpers()
	{
		$this->_isViewHelperInit = true;
		
		return $this;
	}
	
	/**
	 * Returns a plugin object from cache.
	 * If the object does not yet exists, it is created.
	 *
	 * Assumes that class is a valid class
	 *
	 * @param string $type
	 * @param string $class
	 * @param array $options
	 * @return mixed
	 */
	public static function getCachedPlugin($type, $class, array $options = null)
	{
		if (!isset(self::$_pluginCache[$type][$class]))
		{
			if ($type == self::TYPE_FILTER || $type == self::TYPE_VALIDATOR)
			{
				self::setCachedPlugin($type, new $class($options));
			}
			else
			{
				self::setCachedPlugin($type, new $class);
			}
		}
		
		return self::$_pluginCache[$type][$class];
	}
	
	/**
	 * Sets a plugin object to the cache
	 *
	 * Object class is detected abd type is detected
	 * 
	 * @param string $type
	 * @param string $class
	 * @return void
	 */
	public static function setCachedPlugin($type, $object)
	{
		$class = get_class($object);
		
		if (isset(self::$_pluginCache[$type][$class]))
		{
			unset(self::$_pluginCache[$type][$class]);
		}
		
		if ($type == self::TYPE_VIEWHELPER)
		{
			$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
			$object->setView($viewRenderer->view);
		}
		
		self::$_pluginCache[$type][$class] = $object;
	}
	
	/**
	 * Clears the cached plugin
	 *
	 * @return void
	 */
	public static function clearCachedPlugin()
	{
		self::$_pluginCache = array();
	}
	
	/**
	 * Applies options to the given plugin object based on the options passed
	 *
	 * Returns the passed object
	 *
	 * @param string $type
	 * @param mixed $object
	 * @param array $params
	 * @return mixed
	 */
	public static function applyPluginOptions($type, $object, array $params = null)
	{
		if (isset($params['options']) && is_array($params['options']))
		{
			foreach ($params['options'] as $key => $value)
			{
				$method = 'set' . ucfirst($key);
				$object->$method($value);
			}
		}
		
		return $object;
	}
	
	/**
	 * Returns the view string for a field
	 *
	 * @param string 	$field
	 * @param string 	$viewHelper
	 * @param array 	$attribs
	 */
	public function view($field, $viewHelper, array $attribs = null)
	{
		if (!array_key_exists($field, $this->_fields))
		{
			return null;
		}
		
		// before loading any options, this is the chance to intialize the view fully
		if (!$this->_isViewHelperInit)
		{
			$this->initViewHelpers();
		}
		
		$options = $this->_fields[$field]['viewHelpers'][$viewHelper];
		$class = $this->getClassFullName($viewHelper, self::TYPE_VIEWHELPER);
		if (!$class)
		{
			return null;
		}
		
		$obj = self::getCachedPlugin(self::TYPE_VIEWHELPER, $class);
		
		// load parameters
		$name = $field;
		if (isset($options['name']))
		{
			$name = $options['name'];
		}
		
		// value
		$value = $this->getValue($field);
		if (isset($options['value']))
		{
			$value = $options['value'];
		}
		
		// attribs
		$attribs = (array)$attribs;
		if (isset($options['attribs']))
		{
			$attribs += $options['attribs'];
		}
		
		// choices for form selects
		$choices = null;
		if ($viewHelper == 'formSelect' || $viewHelper == 'formRadio')
		{
			if (isset($options['choices']))
			{
				$choices = $options['choices'];
			}
			
			if ($viewHelper == 'formRadio')
			{
				// check if list separator is set on attribs
				if (isset($attribs['listsep']))
				{
					$listsep = $attribs['listsep'];
					unset($attribs['listsep']);
					return $obj->$viewHelper($name, $value, $attribs, $choices, $listsep);
				}
			}
		}
		return $obj->$viewHelper($name, $value, $attribs, $choices);
	}
	
	/**
	 * This is the last chance for the model to prepare the current data
	 *
	 * @return void
	 */
	public function initValidators()
	{
		
	}
	
	/**
	 * Checks the currently loaded values against validators
	 *
	 * @return array
	 * @throws Dc_Validate_Exception
	 */
	public function check()
	{
		// last chance to prepare the data
		$this->initValidators();
		
		// apply filters if present
		$breakChain = false;
		foreach ($this->_fields as $field => $options)
		{
			// if dependent is set, skip validation when the dependent already has errors
			if (isset($options['dependent']))
			{
				$dep = $options['dependent'];
				if (isset($this->_messages[$dep]) && !empty($this->_messages[$dep]))
				{
					// skip
					continue;
				}
			}
			
			// check if the field is required, not required by default
			$required = false;
			if (isset($options['required']) && $options['required'])
			{
				$required = true;
			}
			
			if ($required)
			{
				// insert not empty validator for this field, only when
				// NotEmpty validator is not set
				// take note that validator only exists on this context
				// outside thie function, the NotEmpty validator still does not exists
				if (!isset($options['validators']['NotEmpty']))
				{
					$prepend = array(
						'NotEmpty' => array(
							'options' => array(
								'type' => Zend_Validate_NotEmpty::STRING
							)
						)
					);
					
					$old = (isset($options['validators'])) ? (array)$options['validators'] : array();
					$prepend += $old;
					$options['validators'] = $prepend;
					unset($prepend, $old);
				}
			}
			
			// check if the field will be converted to null when empty
			$nullWhenEmpty = false;
			if (isset($options['nullWhenEmpty']) && $options['nullWhenEmpty'])
			{
				$nullWhenEmpty = true;
			}
			
			// normalize filters into array
			$filters = array();
			if (isset($options['filters']))
			{
				$filters = (array)$options['filters'];
			}
			
			// apply filters to fields if present
			foreach ($filters as $filter => $params)
			{
				$class = $this->getClassFullName($filter, self::TYPE_FILTER);
				if (!$class)
				{
					continue;
				}
				
				$filterOptions = null;
				if (isset($params['options']))
				{
					$filterOptions = (array)$params['options'];
				}
				$f = self::getCachedPlugin(self::TYPE_FILTER, $class, $filterOptions);
				$f = self::applyPluginOptions(self::TYPE_FILTER, $f, $params);
				$this->setValue($field, $f->filter($this->getValue($field)));
				unset($f);
			}
			unset($filters);
			
			// set value to null when empty only when told to do so
			if ($nullWhenEmpty && $this->isEmpty($this->getValue($field)))
			{
				$this->setValue($field, null);
			}
			
			// if the value is empty and this field is not request, skip validation
			if (!$required && $this->isEmpty($this->getValue($field)))
			{
				continue;
			}
			
			// validate fields against validators
			$validators = array();
			if (isset($options['validators']))
			{
				$validators = (array)$options['validators'];
			}
			
			foreach ($validators as $validator => $params)
			{				
				// only run validator if there is no previous error message on this field
				if (isset($this->_messages[$field]) && !empty($this->_messages[$field]))
				{
					continue;
				}
				
				$class = $this->getClassFullName($validator, self::TYPE_VALIDATOR);
				if (!$class)
				{
					continue;
				}
				
				$validateOptions = null;
				if (isset($params['options']))
				{
					$validateOptions = (array)$params['options'];
				}
				
				$v = self::getCachedPlugin(self::TYPE_VALIDATOR, $class, $validateOptions);
				$v = self::applyPluginOptions(self::TYPE_VALIDATOR, $v, $params);
				
				// check if there is a custom error message
				$customMessages = $this->getCustomMessages($field, $validator);
				if (!empty($customMessages))
				{
					$v->setMessages($customMessages);
				}
				
				if (!$v->isValid($this->getValue($field)))
				{
					$msg = $v->getMessages();
					$keys = array_keys($msg);
					$this->_messages[$field][$keys[0]] = reset($msg);
					
					// break chain when set
					if (isset($params['breakChainOnFailure']) && $params['breakChainOnFailure'])
					{
						// break the inner and outer loop
						break 2;
					}
				}
			}
		}
		
		if ($this->hasMessages())
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Returns the custom validator messages for a given
	 * field and validator
	 *
	 * @param string $field
	 * @param string $validator
	 * @return array
	 */
	public function getCustomMessages($field, $validator)
	{
		if (isset($this->_validatorMessages[$field][$validator]))
		{
			return $this->_validatorMessages[$field][$validator];
		}
		
		return null;
	}
	
	/**
	 * Returns true if and only if there are no messages on this model
	 *
	 * @return boolean
	 */
	public function hasMessages()
	{
		return !empty($this->_messages);
	}
	
	/**
	 * Returns messages for this model
	 *
	 * @return array
	 */
	public function getMessages()
	{
		return $this->_messages;
	}
	
	/**
	 * Merges validation messages into a single array
	 *
	 * @param array $messages
	 * @return array
	 */
	public function mergeMessages(array $messages)
	{
		// only one error message per field
		$msg = array();
		foreach ($messages as $field => $message)
		{
			$msg[$field] = reset($message);
		}
		
		return $msg;
	}
}