<?php
/**
* tiny_rest_class.php
* Various functions and what not for the tiny rest server
* @package tiny_rest
* @version 1.0
* @author Liam Bowers <tiny_rest@liambowers.co.uk>
* @copyright Copyright (c) 2013 Liam Bowers
*/

class tiny_rest
{
	private $version = '0.1';

	// /namespace1/namespace2/parameter1/parameter2/?request_variable1=something
	private $namespaces = array();
	private $parameters = array();
	private $parameter_values = array();

	public $request_method = null;
	public $request_variables = array();

	private	$http_status_codes = array(
										200 => 'OK',
										201 => 'Created',
										202 => 'Accepted',
										204 => 'No Content',
										302 => 'Found',
										304 => 'Not Modified',
										400 => 'Bad Request',
										401 => 'Unauthorised',
										403 => 'Forbidden',
										404 => 'Not Found',
										405 => 'Method Not Allowed',
										406 => 'Not Acceptable',
										409 => 'Conflict',
										410 => 'Gone',
										500 => 'Internal Server Error',
										501 => 'Not Implemented',
										503 => 'Service Unavailable',
										504 => 'Gateway Timeout',
										505 => 'HTTP Version Not Supported',
										510 => 'Not Extended'
	);

	const p = 'parameter_null';

	////////////////////////////////////////
	////////////////////////////////////////
	// Grab the namespaces
	////////////////////////////////////////
	////////////////////////////////////////

	public function get_namespaces()
	{
		$request_uri = $_SERVER['REQUEST_URI'];

		if($request_uri == '/') return false;

		$script_path = dirname($_SERVER['SCRIPT_NAME']);

		if(substr($_SERVER['REQUEST_URI'], 0, strlen($script_path)) == $script_path)
		{
			//Remove the path.
			$request_uri = substr($request_uri, strlen($script_path), strlen($request_uri) - strlen($script_path));
		}

		@list($uri, $parameters) = @explode('?', $request_uri, 2);

		$this->uri = $uri;

		if($this->uri == '/') return false;	//domain.com/foo/

		//Split the parts out.
		$url_parts = explode('/', trim($uri, '/'));

		//Build namespaces
		$url_parts_count = count($url_parts);

		//Todo: Sanity check - remove any more than 10 path parts.

		$namespaces = array();

		for($i = $url_parts_count; $i > 0; $i--)
		{
			$this_url_parts = $url_parts;

			$this->namespaces[$i] = implode('.', array_splice($this_url_parts, 0, $i));

			$this->parameters[$i] = $this_url_parts;
		}

		return $this->namespaces;
	}

	////////////////////////////////////////
	////////////////////////////////////////
	// Set parameter values
	////////////////////////////////////////
	////////////////////////////////////////

	public function set_parameter_values_id($parameters_id)
	{
		$this->parameter_values = $this->parameters[$parameters_id];
	}

	////////////////////////////////////////
	////////////////////////////////////////
	// Use remaining parameters
	////////////////////////////////////////
	////////////////////////////////////////

	//This will retrieve the url parts that remain and update the global variables.
	//Can be called multiple times assuming there are still variables to return.
	public function use_parameters(
									&$p1,
									&$p2 = self::p,
									&$p3 = self::p,
									&$p4 = self::p,
									&$p5 = self::p,
									&$p6 = self::p,
									&$p7 = self::p,
									&$p8 = self::p,
									&$p9 = self::p,
									&$p10 = self::p
	)
	{

		$p1 = array_shift($this->parameter_values);
		if($p2 !== self::p) $p2 = array_shift($this->parameter_values);
		if($p3 !== self::p) $p3 = array_shift($this->parameter_values);
		if($p4 !== self::p) $p4 = array_shift($this->parameter_values);
		if($p5 !== self::p) $p5 = array_shift($this->parameter_values);
		if($p6 !== self::p) $p6 = array_shift($this->parameter_values);
		if($p7 !== self::p) $p7 = array_shift($this->parameter_values);
		if($p8 !== self::p) $p8 = array_shift($this->parameter_values);
		if($p9 !== self::p) $p9 = array_shift($this->parameter_values);
		if($p10 !== self::p) $p10 = array_shift($this->parameter_values);

	}

	public function get_request_variables()
	{
		return $this->request_variables;
	}

	////////////////////////////////////////
	////////////////////////////////////////
	// Authentication
	////////////////////////////////////////
	////////////////////////////////////////

	public function authenticate()
	{
		if(isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
		{
			if(
				$_SERVER['PHP_AUTH_USER'] == USERNAME &&
				$_SERVER['PHP_AUTH_PW'] == PASSWORD
			)
			{
				return true;
			}
		}

		return false;
	}

	////////////////////////////////////////
	////////////////////////////////////////
	// Process Request
	////////////////////////////////////////
	////////////////////////////////////////

	public function process_request()
	{
		$request = array();

		$format = explode('/', $_SERVER['HTTP_ACCEPT'], 2);

		//Request method (and data)
		$request_method = strtoupper($_SERVER['REQUEST_METHOD']);

		switch($request_method)
		{
			case 'GET':

				$this->request_method = 'get';

				unset($_GET['url']);	//Remove the url / mod_rewrite string.

				$this->request_variables = $_GET;

				break;

			case 'POST':

				$this->request_method = 'post';
				$this->request_variables = $_POST;

				break;

			case 'PUT':

				$this->request_method = 'put';

				parse_str(file_get_contents('php://input'), $put_vars);

				$this->request_variables = $put_vars;

				break;

			case 'DELETE':

				$this->request_method = 'delete';
		}

		return true;

	}

	////////////////////////////////////////
	////////////////////////////////////////
	// Results
	////////////////////////////////////////
	////////////////////////////////////////

	public function process_response($status_code = 200, $data = array())
	{
		$data = array('tiny_rest_v' => '1.0', 'status_code' => $status_code) + $data;

		//Deal with any content in the buffer to stop corrupted output.
		if($buffer = ob_get_clean())
		{
			//Kill the buffer.
			if(DEBUG) $data['buffer'] = $buffer;
			//trigger_error('debug buffer: \'' . print_r($buffer, true) . '\'', E_USER_NOTICE);
		}

		$content = json_encode($data);

		header('HTTP/1.1 ' . $status_code . ' ' . (isset($this->http_status_codes[$status_code]) ? $this->http_status_codes[$status_code] : 'Unknown error'));
		header('Content-type: application/json');
		header('X-Powered-By: Tiny_Rest');

		echo $content;
	}

	public function success()
	{
		//Allow a status code to be specified if the correct number of arguments and formatting matches.
		if(
			func_num_args() == 2 &&
			(is_int(func_get_arg(0)) &&
			is_array(func_get_arg(1)))
		)
		{
			list($status_code, $data) = func_get_args();
		}
		else
		{
			$status_code = 200;

			list($data) = func_get_args();

			if(is_string($data))
			{
				$data = array('message' => $data);
			}
		}

		$result['status'] = 'ok';
		$result['message'] = 'Completed';

		$this->process_response($status_code, array_merge($result, $data));

		die();
	}

	public function error($status_code = 500, $data = array())
	{
		$result['status'] = 'error';
		$result['message'] = isset($this->http_status_codes[$status_code]) ? $this->http_status_codes[$status_code] : 'Unknown error';

		$this->process_response($status_code, array_merge($result, $data));

		die();
	}

	public function error_handler()
	{
		list($error_code, $error_message, $error_file, $error_line) = func_get_args();

		$result['status'] = 'error';
		$result['message'] = defined('DEBUG') ? $error_message : 'Internal system error';

		$result['error'] = array(
									'code' => $error_code,
									'file' => $error_file,
									'line' => $error_line
								);

		$this->process_response(500, $result);

		die();
	}
}
?>