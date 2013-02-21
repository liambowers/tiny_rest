<?php
/**
* index.php
* A prototype tiny REST server
* @package tiny_rest
* @version 1.0
* @author Liam Bowers <tiny_rest@liambowers.co.uk>
* @copyright Copyright (c) 2013 Liam Bowers
*/

ini_set('display_errors', 1);
//ini_set('error_log', 'tiny_rest.log');

ob_start();	//This causes problems if its not here.

require('config.php');
require('tiny_rest_class.php');

$tiny_rest = new tiny_rest();

//set_error_handler(array($tiny_rest, 'error_handler'));

$tiny_rest->process_request();

if(defined('AUTHORISATION_REQUIRED') && AUTHORISATION_REQUIRED == true && $tiny_rest->authenticate() == false)
{
	$result['message'] = 'Invalid credentials.';
	$tiny_rest->error(401, $result);
}

$namespaces = $tiny_rest->get_namespaces();

foreach($namespaces as $key => $this_namespace)
{
	$module_path = 'modules/' . $this_namespace . '.php';

	if(file_exists($module_path))
	{

		$tiny_rest->set_parameter_values_id($key);

		include($module_path);

		$module_class_name = $this_namespace;

		if(class_exists($module_class_name))
		{
			$api_module = new $module_class_name();

			$request_method = $tiny_rest->request_method;

			if(method_exists($module_class_name, $request_method))
			{
				$api_module->$request_method($tiny_rest);

				//If the script doesn't end with the above module, error as something isn't quite right.
				$tiny_rest->error(510);
			}
			else
			{
				$tiny_rest->error(405);
			}
		}
		else
		{
			$tiny_rest->error(500, array('message' => 'Module error'));
		}
	}
}

$tiny_rest->error(404, array('message' => 'API endpoint doesn\'t exist.'));

?>