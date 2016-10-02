<?php
/**
 * Created by PhpStorm.
 * User: Duby
 * Date: 10/14/2015
 * Time: 12:20 AM
 */

namespace Broadcast;


class Broadcaster implements BroadcasterInterface
{

	protected $test = false;

	protected $services = [];

	/**
	 * Broadcaster constructor.
	 *
	 * @param bool $test
	 */
	public function __construct($test = false)
	{
		$this->test = $test;
	}

	public function addService(ServiceInterface $service)
	{
		$this->services[$service->getName()] = $service;
	}

	public function broadcast($msg)
	{
		if($this->test != false){
			foreach($this->services as $service){
				$service->broadcast($msg);
			}
		} else {
			error_log($msg);
		}
	}

}