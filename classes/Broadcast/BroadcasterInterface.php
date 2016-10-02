<?php
/**
 * Created by PhpStorm.
 * User: Duby
 * Date: 10/14/2015
 * Time: 12:31 AM
 */

namespace Broadcast;


interface BroadcasterInterface
{
	/**
	 * Add a service
	 *
	 * @param ServiceInterface $service
	 * @return mixed
	 */
	public function addService(ServiceInterface $service);

	/**
	 * Send a message through the attached services
	 *
	 * @param $msg string
	 * @return mixed
	 */
	public function broadcast($msg);
}