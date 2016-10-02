<?php
/**
 * Created by PhpStorm.
 * User: Duby
 * Date: 10/14/2015
 * Time: 12:25 AM
 */

namespace Broadcast\Services;

use Broadcast\ServiceInterface;

class Twitter implements ServiceInterface
{
	protected $twitter;

	/**
	 * Twitter constructor.
	 */
	public function __construct()
	{
		$store = new \OAuth\Store\HardCoded();
		$this->twitter = new \OAuth\Service\Twitter($store);
	}


	/**
	 * Gets the name for this service
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'twitter';
	}

	/**
	 * Send along the passed in message
	 *
	 * @param $msg string Message to send
	 */
	public function broadcast($msg)
	{
		$this->twitter->statuses_update(['status' => $msg ]);
	}

}