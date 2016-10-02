<?php
/**
 * Created by PhpStorm.
 * User: Duby
 * Date: 10/14/2015
 * Time: 12:29 AM
 */

namespace Broadcast\Services;


use Broadcast\ServiceInterface;

class ErrorLog implements ServiceInterface
{
	/**
	 * Gets the name for this service
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'error-log';
	}


	/**
	 * Send along the passed in message
	 *
	 * @param $msg string Message to send
	 */
	public function broadcast($msg)
	{
		error_log($msg);
	}

}