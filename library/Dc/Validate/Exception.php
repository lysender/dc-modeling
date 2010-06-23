<?php

class Dc_Validate_Exception extends Exception
{
	/**
	 * @var array
	 */
	public $messages;
	
	/**
	 * __construct()
	 *
	 * @param array $messages
	 * @param string $genericMessage
	 * @param int $code
	 * @return void
	 */
	public function __construct(array $messages, $genericMessage = 'Validation failed', $code = 0)
	{
		$this->messages = $messages;
		
		parent::__construct($genericMessage, $code);
	}
}