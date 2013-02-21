<?php
/**
* ping.php
* Ping test module
* @package tiny_rest
* @version 1.0
* @author Liam Bowers <tiny_rest@liambowers.co.uk>
* @copyright Copyright (c) 2013 Liam Bowers
*/

class test
{
	function get($api)
	{
		$api->success(array('message' => 'debug get'));
	}

	function post($api)
	{
		$api->success(array('message' => 'debug post'));
	}

	function put($api)
	{
		$api->success(array('message' => 'debug put'));
	}

	function delete($api)
	{
		$api->success(array('message' => 'debug delete'));
	}
}

?>