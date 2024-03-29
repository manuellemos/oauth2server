<?php
/*
 * auth2_server_api.php
 *
 * @(#) $Id: $
 *
 */
 
class oauth2_server_api_class
{
	public $error = '';
	public $exit = false;
	public $options;

	private $error_code = OAUTH2_ERROR_NONE;
	private $error_message = '';
	private $case;
	private $format;
	private $supported_response_formats = array('json'=>true);
	private $default_format = 'json';

	Function SetAPIError($error_code, $error_message)
	{
		$this->options->OutputDebug('API error '.$error_code.': '.$error_message);
		$this->error_code = $error_code;
		$this->error_message = $error_message;
		return true;
	}

	Function ValidateAccessToken($access_token, &$valid, &$user)
	{
		if(!$this->handler->ValidateAccessToken($access_token, $valid, $user))
			return false;
		$valid = ($this->handler->error_code === OAUTH2_ERROR_NONE);
		if(!$valid)
			return $this->SetAPIError($this->handler->error_code, $this->handler->error_message);
		return true;
	}

	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the token class';
			return false;
		}
		$this->options->debug_prefix = 'OAuth server api: ';
		$this->handler = new $this->options->server_handler;
		$this->handler->options = $this->options;
		if(!$this->handler->Initialize())
		{
			$this->error = $this->handler->error;
			return false;
		}
		return true;
	}

	Function Process()
	{
		$this->options->OutputDebug('Checking the API access token...');
		$access_token = IsSet($_GET['access_token']) ? $_GET['access_token'] : null;
		if(!$this->ValidateAccessToken($access_token, $valid, $user))
			return false;
		$this->options->OutputDebug('The API access token ('.(IsSet($access_token) ? $access_token : '"not set"').') is '.($valid ? 'valid' : 'invalid').'.');
		if(!$valid)
			return true;
		$uri = $_SERVER['REQUEST_URI'];
		$method = $_SERVER['REQUEST_METHOD'];
		$this->options->OutputDebug('Checking the API call URI: '.$uri);
		$this->options->OutputDebug('HTTP request method: '.$method);
		$matched = null;
		foreach($this->options->api as $name => $api)
		{
			if((!IsSet($api['pattern'])
			|| strpos($uri, $api['pattern']) === false)
			&& (!IsSet($api['startpattern'])
			|| substr($uri, 0, strlen($api['startpattern'])) !== $api['startpattern']))
				continue;
			$matched = $name;
			if(IsSet($api['methods'])
			&& !in_array($method, $api['methods']))
				continue;
			$this->case = new $api['usecaseclass'];
			$format = $this->default_format;
			if(IsSet($api['formatparameter']))
			{
				$parameter = $api['formatparameter'];
				if(IsSet($_GET[$parameter]))
					$format = $_GET[$parameter];
			}
			if(!IsSet($this->supported_response_formats[$format]))
				return $this->SetAPIError(OAUTH2_ERROR_UNSUPPORTED_API_RESPONSE_TYPE, $format.' is not a supported response format');
			$this->format = $format;
			switch($method)
			{
				case 'POST':
					$request_content_type = (IsSet($api['requestcontenttype']) ? $api['requestcontenttype'] : 'application/x-www-form-urlencoded');
					switch($request_content_type)
					{
						case 'application/x-www-form-urlencoded':
							$parameters = $_POST;
							break;
						case 'application/json':
							if(!($request_data = file_get_contents('php://input')))
							{
								$this->error = 'it was not possible to read the API request parameters';
								echo $a;
								$error = error_get_last();
								if(IsSet($error)
								&& IsSet($error['message']))
									$this->error .= ': '.$error['message'];
								return false;
							}
							$this->options->OutputDebug('Request parameters data: '.print_r($request_data, 1));
							$decoded_request_data = json_decode($request_data);
							$type = GetType($decoded_request_data);
							switch($type)
							{
								case 'object':
								case 'array':
									break;
								default:
									return $this->SetAPIError(OAUTH2_ERROR_UNSUPPORTED_API_REQUEST_TYPE, $type.' is not yet a supported request JSON data type');
							}
							$parameter = (IsSet($api['getrequestdata']) ? $api['getrequestdata'] : 'requestdata');
							$parameters = array($parameter => json_decode($request_data));
							break;
						default:
							return $this->SetAPIError(OAUTH2_ERROR_UNSUPPORTED_API_REQUEST_TYPE, $request_content_type.' is not yet a supported request format');
					}
					break;
				case 'GET':
					$parameters = $_GET;
					break;
				default:
					$parameters = array();
					break;
			}
			$this->options->OutputDebug('Request parameters: '.print_r($parameters, 1));
			if(IsSet($api['getuser']))
				$parameters[$api['getuser']] = $user;
			if(IsSet($api['getaction']))
			{
				$action = $api['getaction'];
				$parameters[$action['action']] = $action['value'];
			}
			$this->case->options = $this->options;
			if(($success = $this->case->initialize()))
			{
				$this->options->OutputDebug('Executing the API call: '.$name);
				$success = $this->case->call($parameters);
				$success = $this->case->finalize($success);
			}
			if(!$success)
			{
				$this->error = $this->case->error;
				$error = $this->error;
				if(GetType($error) !== 'string')
					$error = serialize(error);
				$this->options->OutputDebug('Error: '.$error);
				return false;
			}
			$this->exit = $this->case->exit;
			return true;
		}
		return $this->SetAPIError(OAUTH2_ERROR_INVALID_API_CALL, IsSet($matched) ? 'the HTTP method '.$method.' is not supported for API call '.$matched : 'it was not called a supported API method');
	}
	
	Function Finalize($success)
	{
		if(!$this->handler->Finalize($success))
		{
			if($success)
			{
				$success = false;
				$this->error = $this->handler->error;
			}
		}
		return $success;
	}

	Function Output()
	{
		if($this->error_code === OAUTH2_ERROR_NONE)
		{
			$output = $this->case->output();
			$content_type = 'text/plain;charset=UTF-8';
			if(IsSet($this->format))
			{
				switch($this->format)
				{
					case 'json':
						$output = json_encode($output);
						$content_type = 'application/json;charset=UTF-8';
						break;
				}
			}
			Header('HTTP/1.1 200 OK');
			Header('Content-Type: '.$content_type);
			Header('Access-Control-Allow-Origin: *');
			echo $output;
		}
		else
		{
			$output = new stdClass;
			$output->message = $this->error_message;
			switch($this->error_code)
			{
				case OAUTH2_ERROR_INVALID_API_CALL:
				case OAUTH2_ERROR_UNSUPPORTED_API_RESPONSE_TYPE:
					Header('HTTP/1.1 400 Bad Request');
					break;

				default:
					Header('HTTP/1.1 404 Not Found');
					break;

			}
			Header('Content-Type: application/json;charset=UTF-8');
			Header('Access-Control-Allow-Origin: *');
			echo json_encode($output);
		}
	}
};

?>