<?php
/**
* ping.php
* Ping test module
* @package tiny_rest
* @version 1.0
* @author Liam Bowers <tiny_rest@liambowers.co.uk>
* @copyright Copyright (c) 2013 Liam Bowers
*/

class ping
{
	function get($api)
	{
		$api->success(array('pong' => time()));
	}
}

?>