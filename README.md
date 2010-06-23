# DC Modeling for Zend Framework

A modeling library to simplify modeling needs in Zend Framework.
Based on Kohana's Sprig modeling but uses Zend Framework components.

*DISCLAIMER:* The codes and the sample documentation is not complete
and continues to be develop, used and refactored. The sample tutorial may
not work in some cases. Please inform me if you have some thoughts.

## Components

* Model - abstract model / base class for all models
* Mapper - data mapper
* Validators - uses Zend Framework's Zend_Validate classes
* Filters - uses Zend Framework's Zend_Filter classes
* View Helpers - uses Zend Framework's Zend_View_Helper classes
* Exceptions - exception classes for mapper, model and validate

## Setup

To install and use the modeling library, put it on the library directory
for every Zend Framework project and autoload them. All classes expects
to be autoloaded.

For a basic Zend Framework project that uses Zend_Application and
utilizes autoloaders in Bootstrap, you may add this method:

application/Bootstrap.php

    /**
     * Autoloads project library
     */
    protected function _initProjectAutoloader()
    {
    	$autoloader = Zend_Loader_Autoloader::getInstance();
    	$autoloader->registerNamespace('Dc_');
    }

## Usage

Basic usage for models.

### Extending the basic model class

To use the modeling library, create a model and extend Dc_Model_Abstract

ex: application/models/Customer.php

	class Default_Model_Customer extends Dc_Model_Abstract
	{
		protected $_name = 'customer';
		
		protected $_namespace = 'Default_';
		
		protected $_notMapped = array(
			'birth_date_day',
			'birth_date_month',
			'birth_date_year'
		);
		
		/**
		 * Initialize
		 *
		 * @return void
		 */
		public function init()
		{
			$this->_fields += array(
				'customer_id' 		=> array(),
				'title'				=> array(
					'required'		=> true,
					'viewHelpers'	=> array(
						'formSelect'	=> null
					)
				),
				'first_name'		=> array(
					'required'		=> true,
					'filters'			=> array(
						'StringTrim'		=> null
					),
					'validators'		=> array(
						'StringLength'		=> array(
							'options'			=> array(
								'min'				=> 2,
								'max'				=> 30
							)
						)
					),
					'viewHelpers'		=> array(
						'formText'			=> null
					)
				)
			);
		}
	}

The _name property is the name of the model and will also be the data
mapper name prefix.

The _namespace property is the class prefix for models. It can be
'Application_', 'Default_', or any prefix that you are using for your
default module. It could be an empty string if you like.

The _notMapped property is an array of fields that are not mapped
to the data mapper / data source but are used by the model such as
tokens, validation fields, fields split into several fields such as dates
and the like.

For other properties, see Dc_Model_Abstract

The _init() method initializes the model's fields. It is called when the model
is initialized. It usually initializes the model's fields.

The _field proprty is an array containing the fields for the model. Each element
for this array represents a field.

### Field details

For each element in the _fields array, the key is the field name and the value
is the options for the fields which are the following:

* required - true/false
* nullWhenEmpty - true/false
* dependent - other field's name where if that field does not pass validation,
this field will not be validated
* filters - an array of filters
* validators - an array of validators
* viewHelpers - an array of view helpers

All of these options are optional.
	/*
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
	 */

### Creating a data mapper

To allow data interaction, one must create a data mapper with the same name
as the model name and suffixed with "Mapper".

ex: for Default_Model_Customer, we need to create
Default_Model_CustomerMapper class for data mapping pattern. The mapper name
must be the same as the one define on the model's protected property

	$this->_name = 'customer';

The name must start with lower case letter. The mapper class will start
with upper case. For example:

	class Default_Model_User extends Dc_Model_Abstract
	{
		protected $_name = 'user';
	}
	
The mapper should be:

	class Default_Model_UserMapper extends Dc_Mapper_Db{}
	
	// or
	
	class Default_Model_UserMapper implements Dc_Mapper_Interface{}
	
	// for custom mappers
	
### Mapper basic methods

The Dc_Mapper_Db which uses Zend_Db as abstraction layer provides basic
methods:

* get
* insert
* update
* delete
* find

## Example

User table

### Database design

* username - string, primary
* password - string, hash 64 length
* last_login - datetime
* active - tinyint

### Database connection

The database connectivity should be established via Zend_Db_Table default adapter,
otherwise, you need to manually set the database adapter on the mapper.

If it is already configured, then let's move on.

### Mapper first

Default_Model_UserMapper

	class Default_Model_UserMapper extends Dc_Mapper_Db
	{
		protected $_table = 'user';
		
		/**
		 * Returns all users
		 *
		 * @return array
		 */
		public function getAll()
		{
			$db = $this->getDb();
			$select = $db->select()
				->from($this->_table)
				->order('username ASC');
				
			return $db->fetchAll($select);
		}
	}
	
Our mapper needs a custom method to get all users, however, we can also
use the find method from the parent class.

### The model

	<?php	
	class Default_Model_User extends Dc_Model_Abstract
	{	
		protected $_name = 'user';
		
		protected $_namespace = 'Default_';
		
		protected $_salt = '4b0fe9bffe83cdbaa4';
		
		protected $_notMapped = array(
			'token',
			'confirm_password'
		);
		
		protected $_validatorMessages = array(
			'username'		=> array(
				'NotEmpty' 		=> array(
					Zend_Validate_NotEmpty::IS_EMPTY => 'Username not entered'
				),
				'StringLength'	=> array(
					Zend_Validate_StringLength::INVALID => 'Username not entered',
					Zend_Validate_StringLength::TOO_LONG => 'Username is only up to %max% characters',
					Zend_Validate_StringLength::TOO_SHORT => 'Username is at least %min% characters'
				),
				'Alnum'			=> array(
					Zend_Validate_Alnum::INVALID => 'Username must be composed of letters and numbers only',
					Zend_Validate_Alnum::NOT_ALNUM => 'Username must be composed of letters and numbers only'
				),
				'Callback'		=> array(
					Zend_Validate_Callback::INVALID_VALUE => 'Username already exists',
					Zend_Validate_Callback::INVALID_CALLBACK => 'Invalid callback supplied'
				)
			),
			'password'		=> array(
				'NotEmpty' 		=> array(
					Zend_Validate_NotEmpty::IS_EMPTY => 'Password not entered'
				),
				'StringLength'	=> array(
					Zend_Validate_StringLength::INVALID => 'Password not entered',
					Zend_Validate_StringLength::TOO_LONG => 'Password is only up to %max% characters',
					Zend_Validate_StringLength::TOO_SHORT => 'Password is at least %min% characters'
				)
			),
			'confirm_password' => array(
				'NotEmpty' 		=> array(
					Zend_Validate_NotEmpty::IS_EMPTY => 'Confirm password not entered'
				),
				'Identical'		=> array(
					Zend_Validate_Identical::NOT_SAME => 'Passwords did not match',
					Zend_Validate_Identical::MISSING_TOKEN => 'Passwords did not match',
				)
			),
			'token'			=> array(
				'NotEmpty'		=> array(
					Zend_Validate_NotEmpty::IS_EMPTY => 'Session timeout, try again'
				),
				'Identical'		=> array(
					Zend_Validate_Identical::MISSING_TOKEN => 'Session timeout, try again',
					Zend_Validate_Identical::NOT_SAME => 'Session timeout, try again'
				)
			)
		);
		
		/**
		 * Initialize class
		 *
		 * @return void
		 */
		public function init()
		{
			$this->_fields += array(
				'token'			=> array(
					'required'		=> true,
					'validators'	=> array(
						'Identical'		=> array(
							'breakChainOnFailure' => true,
							'options'		=> array(
								'token'			=> $this->getSessionValue('token')
							)
						)
					),
					'viewHelpers'	=> array(
						'formHidden'	=> null
					)
				),
				'username' 		=> array(
					'required'		=> true,
					'filters'		=> array(
						'StringTrim'	=> null
					),
					'validators'	=> array(
						'StringLength'	=> array(
							'options'		=> array(
								'min'			=> 4,
								'max'			=> 16
							)
						),
						'Alnum'			=> null,
					),
					'viewHelpers'	=> array(
						'formText'		=> null
					)
				),
				'password'		=> array(
					'required'		=> true,
					'filters'		=> array(
						'StringTrim'	=> null,
					),
					'validators'	=> array(
						'StringLength'	=> array(
							'options'		=> array(
								'min'			=> 4,
								'max'			=> 16
							)
						)
					),
					'viewHelpers'	=> array(
						'formPassword'	=> null
					)
				),
				'last_login'	=> null,
				'active'		=> null
			);
		}
		
		/**
		 * Override view to initialize token
		 *
		 * @param string 	$field
		 * @param string 	$viewHelper
		 * @param array 	$attribs
		 */
		public function view($field, $viewHelper, array $attribs = null)
		{
			if ($field == 'token')
			{
				// initialize token
				$token = sha1($this->_salt . time());
				$this->setValue('token', $token);
				$this->setSessionValue('token', $token);
			}
			
			return parent::view($field, $viewHelper, $attribs);
		}
		
		/**
		 * Login process main wrapper. Returns true only, otherwise an
		 * exception is thrown
		 *
		 * @return boolean
		 * @throws Exception
		 */
		public function loginProcess()
		{
			// check
			if (!$this->check())
			{
				$messages = $this->getMessages();
				throw new Dc_Validate_Exception($this->mergeMessages($messages));
			}
			
			// login
			if (!$this->login())
			{
				throw new Dc_Model_Exception('Login failed for user: ' . $this->getValue('username'));
			}
			return true;
		}
		
		/**
		 * Login to the system
		 * 
		 * @return boolean
		 */
		public function login()
		{
			$db = $this->getMapper()->getDb();
			$db->getConnection();
			
			$auth = Zend_Auth::getInstance();
			$authAdapter = new Zend_Auth_Adapter_DbTable($db);
			
			// only active users can login
			$select = $authAdapter->getDbSelect();
			$select->where('active = ?', 1);
			
			// parameters
			$authAdapter->setTableName($this->getMapper()->getTable())
				->setIdentityColumn('username')
				->setCredentialColumn('password')
				->setIdentity($this->getValue('username'))
				->setCredential($this->_hash($this->getValue('password')));
					
			$result = $auth->authenticate($authAdapter);
			if ($result->isValid())
			{
				$this->setLastLogin($auth->getIdentity());
				
				return true;
			}
			return false;
		}
		
		/**
		 * Initializes adding of user
		 *
		 * @return $this
		 */
		public function initAddUser()
		{
			// add callback so that username must be unique
			$this->setValidator('username', 'Callback', array(
				'breakChainOnFailure' => true,
				'options' => array(
					'callback' => array($this, 'usernameUnique')
				)
			));
			
			// add confirm password field and its options
			$this->setField('confirm_password', array(
				'required'		=> true,
				'filters'		=> array(
					'StringTrim'	=> null,
				),
				'validators'	=> array(
					'Identical'		=> array()
				),
				'viewHelpers'	=> array(
					'formPassword'	=> null
				)
			));
		}
		
		/**
		 * Initialize validators
		 *
		 */
		public function initValidators()
		{
			if ($this->hasField('confirm_password'))
			{
				$this->_fields['confirm_password']['validators']['Identical']['options']['token'] = $this->getValue('password');
			}
		}
		
		/**
		 * Adds a new user
		 *
		 * @return boolean
		 * @throws Dc_Validate_Exception
		 */
		public function addProcess()
		{
			if (!$this->check())
			{
				$messages = $this->getMessages();
				throw new Dc_Validate_Exception($this->mergeMessages($messages));
			}
			
			if (!$this->add())
			{
				throw new Dc_Model_Exception('Unable to add new user');
			}
			
			return true;
		}
		
		/**
		 * Adds a new user to the system
		 *
		 * @return boolean
		 */
		public function add()
		{
			$data = $this->getMappedValues();
			$data['password'] = $this->_hash($data['password']);
			$data['active'] = 1;
			
			return $this->getMapper()->insert($data);
		}
		
		/**
		 * Returns hashed and salted string value
		 *
		 * @return string
		 */
		protected function _hash($value)
		{
			return sha1($this->_salt . $value);
		}
		
		/**
		 * Sets the last login for a user
		 *
		 * @param string 	$username
		 * @param string	$date
		 * @return void
		 */
		public function setLastLogin($username, $date = null)
		{
			$mapper = $this->getMapper();
			return $mapper->update(
				array('username' => $username),
				array('last_login' => ($date) ? $date : date('Y-m-d H:i:s'))
			);
		}
		
		/**
		 * Returns true if the username is unique / don't exists
		 *
		 * @param mixed $value
		 * @return boolean
		 */
		public function usernameUnique($username)
		{
			return !$this->userExists($username, false);
		}
		
		/**
		 * Returns true if the user exists
		 *
		 * @param string $username
		 * @param boolean $activeOnly
		 * @return boolean
		 */
		public function userExists($username, $activeOnly = true)
		{
			$mapper = $this->getMapper();
			$keys = array(
				'username' => $username,
			);
			
			if ($activeOnly)
			{
				$keys['active'] = 1;
			}
			
			$data = $mapper->get($keys);
			return !empty($data);
		}
	}
	?>
	
### The controller VIEW/ADD

	<?php
	
	class UserController extends Zend_Controller_Action
	{
		public function indexAction()
		{
			$this->view->headTitle('User Management');
			
			$user = new Default_Model_User;
			$users = $user->getMapper()->getAll();
			
			$this->view->users = $users;
		}
		
		public function addAction()
		{
			$this->view->headTitle('User Management - Add User');
			$user = new Default_Model_User;
			$user->initAddUser();
			
			$request = $this->getRequest();
			if ($request->isPost())
			{
				$user->setValues($request->getPost());
				try {
					$user->addProcess();
					$this->_redirect('/user');
				}
				catch (Dc_Model_Exception $e)
				{
					var_dump($e->getMessage());
				}
				catch (Dc_Validate_Exception $e)
				{
					var_dump($e->messages);
				}
				catch (Exception $e)
				{
					$this->_logger->emerg(
						'An error occured while adding a new user: '
						. $e->getMessage()
					);
					var_dump($e->getMessage());
				}
			}
			$this->view->user = $user;
		}
	}
	?>
	
### View for user/index or view users

	<h1><strong>User Management</strong></h1>
	
	<div class="entries">
		<div class="entry-body">
			<p><a href="<?php echo $this->baseUrl('admin') ?>">Back to admin</a></p>
			
			<p><a href="<?php echo $this->baseUrl('user/add') ?>">NEW USER</a></p>
			<table class="reg-list">
				<thead>
					<tr>
						<th>Username</th>
						<th>Last login</th>
						<th>Status</th>
						<th>&nbsp;</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
				<?php $users = (array)$this->users; ?>
				<?php foreach ($users as $key => $user): ?>
					<tr>
						<td><?php echo $this->escape($user['username']) ?></td>
						<td><?php echo $this->escape($user['last_login']) ?></td>
						<td><?php echo ($user['active']) ? 'Active' : 'Inactive' ?></td>
						<td class="t-center"><a href="<?php echo $this->baseUrl('user/edit/username/' . $this->escape($user['username'])) ?>">Edit</a></td>
						<td class="t-center"><a href="<?php echo $this->baseUrl('user/delete/username/' . $this->escape($user['username'])) ?>">Delete</a></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
			
		</div>
	</div>
	
### View for user/add

	<h1><strong>Add User - Users</strong></h1>
	
	<p><a href="<?php echo $this->baseUrl('user') ?>">Back to users</a></p>
	
	<div class="reg-form mini-form">
	<form id="user_form" action="<?php echo $this->baseUrl('user/add') ?>" method="post" enctype="multipart/form-data">	
		<div class="block">
			<div class="lbl">
				<label for="username">User name</label>
			</div>
			<div class="input"><?php echo $this->user->view('username', 'formText') ?></div>
		</div><!-- block -->
		
		<div class="block">
			<div class="lbl">
				<label for="password">Password</label>
			</div>
			<div class="input"><?php echo $this->user->view('password', 'formPassword') ?></div>
		</div><!-- block -->
		
		<div class="block">
			<div class="lbl">
				<label for="confirm_password">Confirm password</label>
			</div>
			<div class="input"><?php echo $this->user->view('confirm_password', 'formPassword') ?></div>
		</div><!-- block -->
		
		<div class="submit-block">
			<div class="btn">
				<?php echo $this->user->view('token', 'formHidden') ?>
				<input type="submit" name="submit" id="submit" value="Save" />
			</div>
		</div><!-- block -->
	</form>
	</div><!-- reg-form -->
	<div class="clearer"></div>
	
### The login controller

	<?php
	
	class LoginController extends Dc_Controller_Template
	{		
		public function indexAction()
		{
			$this->view->headTitle('Login');
				
			$request = $this->getRequest();
			
			$user = new Default_Model_User;
			if ($request->isPost())
			{
				$user->setValues($request->getPost());
				try {
					$user->loginProcess();
					
					$this->_redirect('/menu');
				}
				catch (Dc_Model_Exception $e)
				{
					var_dump($e->getMessage());
				}
				catch (Dc_Validate_Exception $e)
				{
					var_dump($e->messages);
				}
				catch (Exception $e)
				{
					$this->_logger->emerg(
						'An error occured while loggin in. '
						. $e->getMessage()
					);
					var_dump($e->getMessage());
				}
			}
			$this->view->user = $user;
		}
		
		public function logoutAction()
		{
			$this->getHelper('layout')->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
		
			$auth = Zend_Auth::getInstance();
			$auth->clearIdentity();
			
			$this->_redirect('/login');
		}
	}
	?>
	
### Login view login/index

	<h1><strong>System Login</strong></h1>
	
	<div class="reg-form mini-form">
	<form id="login_form" action="<?php echo $this->baseUrl('login') ?>" method="post" enctype="multipart/form-data">	
		<div class="block">
			<div class="lbl">
				<label for="username">User name</label>
			</div>
			<div class="input"><?php echo $this->user->view('username', 'formText') ?></div>
		</div><!-- block -->
		
		<div class="block">
			<div class="lbl">
				<label for="username">Password</label>
			</div>
			<div class="input"><?php echo $this->user->view('password', 'formPassword') ?></div>
		</div><!-- block -->
	
		<div class="submit-block">
			<div class="btn">
				<?php echo $this->user->view('token', 'formHidden') ?>
				<input type="submit" name="submit" id="submit" value="Login" />
			</div>
		</div><!-- block -->
	</form>
	</div><!-- reg-form -->
	<div class="clearer"></div>