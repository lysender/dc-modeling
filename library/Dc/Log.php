<?php
 
 class Dc_Log
 {
	/**
	 * @var Zend_Log_Writer_Stream
	 */
 	protected $_writer;
	
	/**
	 * @var Zend_Log
	 */
 	protected $_logger;
	
	/**
	 * Current log filename
	 * 
	 * @var string
	 */
 	protected $_file;
 	
	/**
	 * @var Dc_Log
	 */
	protected static $_instance;
	
	/**
	 * Returns the singleton instance of the object
	 *
	 * @return Dc_Log
	 */
	public static function getInstance()
	{
		if (self::$_instance === null)
		{
			self::$_instance = new self;
		}
		
		return self::$_instance;
	}
	
	/**
	 * Enforces singleton pattern
	 *
	 * @return void
	 */
	protected function __construct()
	{
		// year folder for the log
		$year = APPLICATION_PATH . '/data/logs/' . date('Y');
		if (!is_dir($year))
		{
			// Create the yearly directory
			mkdir($year, 0777);
			
			// Set permissions (must be manually set to fix umask issues)
			chmod($year, 0777);
		}
		
		// month folder for the log
		$month = $year . '/' . date('m');
		if (!is_dir($month))
		{
			// Create the monthly directory
			mkdir($month, 0777);
			
			// Set permissions (must be manually set to fix umask issues)
			chmod($month, 0777);
		}
		
		// log file
		$this->_file = $month . '/' . date('d') . '.log';
		if (!file_exists($this->_file))
		{
			// Create the log file
			file_put_contents($this->_file, '');
			
			// Allow anyone to write to log files
			chmod($this->_file, 0666);
		}
		$this->_writer = new Zend_Log_Writer_Stream($this->_file);
		$this->_logger = new Zend_Log($this->_writer);
	}
 	
	/**
	 * Writes information log
	 *
	 * @param string $message
	 * @return $this
	 */
 	public function info($message)
 	{
 		return $this->_logger->info($message);
 	}

	/**
	 * Writes error / emergency logs
	 *
	 * @param string $message
	 * @return $this
	 */ 	
  	public function emerg($message)
 	{
 		return $this->_logger->emerg($message);
 	}
 }