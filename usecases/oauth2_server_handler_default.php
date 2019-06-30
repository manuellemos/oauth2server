<?php
/*
 * auth2_server_handler_default.php
 *
 * @(#) $Id: $
 *
 */
 
class oauth2_server_handler_default_class
{
	public $error = '';
	public $options;
	public $error_code = OAUTH2_ERROR_NONE;
	public $error_message = '';

	Function SetHandlerError($error_code, $error_message)
	{
		$this->error_code = $error_code;
		$this->error_message = $error_message;
		return true;
	}
	
	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the authorization class';
			return false;
		}
		return true;
	}

	Function Finalize($success)
	{
		return $success;
	}

	Function GenerateRandomCode(&$code)
	{
		$code = md5(uniqid(rand(), true));
		return true;
	}

	Function CheckRedirectURI($redirect_uri, &$valid, &$match)
	{
		$valid = true;
		$match = true;
		return true;
	}

	Function CheckClientID($client_id, &$valid)
	{
		$valid = true;
		return true;
	}

	Function CheckResponseCode($parameters, $code, &$valid)
	{
		$valid = true;
		return true;
	}

	Function GenerateResponseCode($parameters, &$code)
	{
		do
		{
			if(!$this->GenerateRandomCode($code))
				return false;
			if(!$this->CheckResponseCode($parameters, $code, $valid))
				return false;
		}
		while(!$valid);
		return true;
	}
	
	Function CheckAccessToken($parameters, $token, &$valid)
	{
		$valid = true;
		return true;
	}

	Function GenerateAccessToken($parameters, &$access_token)
	{
		do
		{
			if(!$this->GenerateRandomCode($token))
				return false;
			if(!$this->CheckAccessToken($parameters, $token, $valid))
				return false;
		}
		while(!$valid);
		$access_token = array(
			'access_token'=>$token
		);
		return true;
	}

	Function ValidateRedirectURI($redirect_uri)
	{
		if(!IsSet($redirect_uri))
			return $this->SetHandlerError(OAUTH2_ERROR_MISSING_PARAMETER_REDIRECT_URI, 'the redirect_uri parameter is missing');
		if(!$this->CheckRedirectURI($redirect_uri, $valid, $match))
			return false;
		if(!$valid)
			return $this->SetHandlerError(OAUTH2_ERROR_INVALID_PARAMETER_REDIRECT_URI, 'the redirect_uri parameter is invalid');
		if(!$match)
			return $this->SetHandlerError(OAUTH2_ERROR_MISMATCHING_PARAMETER_REDIRECT_URI, 'the redirect_uri parameter does not match the accepted URI pattern');
		return true;
	}

	Function ValidateClientID($client_id)
	{
		if(!$this->CheckClientID($client_id, $valid))
			return false;
		if(!$valid)
			return $this->SetHandlerError(OAUTH2_ERROR_INVALID_PARAMETER_CLIENT_ID, 'the client_id parameter is invalid');
		return true;
	}

};

?>