<?php
/**
 * Created by PhpStorm.
 * User: Duby
 * Date: 10/14/2015
 * Time: 12:23 AM
 */

namespace Broadcast;


interface ServiceInterface
{

	/**
	 * Send along the passed in message
	 *
	 * @param $msg string Message to send
	 */
	public function broadcast($msg);

	/**
	 * Gets the name for this service
	 *
	 * @return string
	 */
	public function getName();

}