<?php
/*
 * auth2_server_token.php
 *
 * @(#) $Id: $
 *
 */
 
class oauth2_server_token_class
{
	public $error = '';
	public $exit = false;
	public $options;

	private $error_code = OAUTH2_ERROR_NONE;
	private $error_message = '';
	private $access_token = null;

	Function SetTokenError($error_code, $error_message)
	{
		$this->options->OutputDebug('Token error '.$error_code.': '.$error_message);
		$this->error_code = $error_code;
		$this->error_message = $error_message;
		return true;
	}
	
	Function ValidateRedirectURI($redirect_uri)
	{
		if(!$this->handler->ValidateRedirectURI($redirect_uri))
			return false;
		if($this->handler->error_code !== OAUTH2_ERROR_NONE)
			return $this->SetTokenError($this->handler->error_code, $this->handler->error_message);
		return true;
	}
	
	Function ValidateClientID($client_id)
	{
		if(!$this->handler->ValidateClientID($client_id))
			return false;
		if($this->handler->error_code !== OAUTH2_ERROR_NONE)
			return $this->SetTokenError($this->handler->error_code, $this->handler->error_message);
		return true;
	}

	Function GenerateAccessToken($parameters)
	{
		if(!$this->handler->GenerateAccessToken($parameters, $this->access_token))
			return false;
		if($this->handler->error_code !== OAUTH2_ERROR_NONE)
			return $this->SetTokenError($this->handler->error_code, $this->handler->error_message);
		return true;
	}

	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the token class';
			return false;
		}
		$this->options->debug_prefix = 'OAuth server token: ';
		$this->options->LoadLocale('token');
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
		$this->options->OutputDebug('Checking the redirect URI...');
		$redirect_uri = (IsSet($_POST['redirect_uri']) ? $_POST['redirect_uri'] : null);
		if(!$this->ValidateRedirectURI($redirect_uri))
			return false;
		if($this->error_code !== OAUTH2_ERROR_NONE)
			return true;
		$this->options->OutputDebug('The redirect URI is: '.$redirect_uri);
		$this->options->OutputDebug('Checking the client id...');
		$client_id = (IsSet($_POST['client_id']) ? $_POST['client_id'] : null);
		if(!$this->ValidateClientID($client_id))
			return false;
		if($this->error_code !== OAUTH2_ERROR_NONE)
			return true;
		$this->options->OutputDebug('The client id is: '.$client_id);
		$this->options->OutputDebug('Checking the response type...');
		$grant_type = (IsSet($_POST['grant_type']) ? $_POST['grant_type'] : '');
		switch($grant_type)
		{
			case 'authorization_code':
				break;
			default:
				return $this->SetTokenError(OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE, 'The provided value for the input parameter \'authorization_code\' is not yet supported. Supported values are the following: \'authorization_code\'');
		}
		$this->options->OutputDebug('The grant type is: '.$grant_type);
		$client_secret = (IsSet($_POST['client_secret']) ? $_POST['client_secret'] : null);
		switch($grant_type)
		{
			case 'authorization_code':
				$this->options->OutputDebug('Generating the access token...');
				$parameters = array(
					'redirect_uri'=>$redirect_uri,
					'grant_type'=>$grant_type,
					'client_id'=>$client_id,
					'client_secret'=>$client_secret
				);
				return $this->GenerateAccessToken($parameters);
			default:
				return $this->SetAuthorizationError(OAUTH2_ERROR_UNEXPECTED_SITUATION, 'the access token generation process is not yet ready to handle grants of type '.$grant_type);
		}
		return $this->SetTokenError(OAUTH2_ERROR_UNEXPECTED_SITUATION, 'the access token generation process is not yet fully implemented');
	}
	
	Function Finalize($success)
	{
		if(!$this->handler->Finalize($success))
		{
			$success = false;
			$this->error = $this->handler->error;
		}
		return $success;
	}

	Function Output()
	{
		if($this->error_code === OAUTH2_ERROR_NONE)
		{
			Header('HTTP/1.1 200 OK');
			Header('Content-Type: application/json;charset=UTF-8');
			Header('Cache-Control: no-store');
			Header('Pragma: no-cache');
			echo "{\n";
			$first = true;
			foreach($this->access_token as $name => $value)
			{
				if($first)
					$first = false;
				else
					echo ",\n";
				echo json_encode($name).":".json_encode($value);
			}
			echo "\n}\n";
		}
		else
		{
			Header('HTTP/1.1 400 Bad Request');
			Header('Content-Type: application/json;charset=UTF-8');
			Header('Cache-Control: no-store');
			Header('Pragma: no-cache');
			echo "{\n";
			echo "\"error\":\"invalid_request\",\n";
			echo "\"error\":".json_encode($this->error_message)."\n";
			echo "}\n";
		}
	}
};

?>