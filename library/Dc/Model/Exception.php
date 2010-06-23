<?php

class Dc_Model_Exception extends Exception
{
	const INFO = 0;
	const EMERG = 1;
	
	public function __construct($message, $type = 0, $code = 0)
	{
		$method = ($type == self::EMERG) ? 'emerg' : 'info';
		Dc_Log::getInstance()->$method($message);
		
		parent::__construct($message, $code);
	}
}