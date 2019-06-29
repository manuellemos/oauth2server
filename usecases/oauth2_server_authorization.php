<?php
/*
 * auth2_server_authorization.php
 *
 * @(#) $Id: $
 *
 */
 
define('OAUTH2_ERROR_NONE',                               0);
define('OAUTH2_ERROR_MISSING_PARAMETER_REDIRECT_URI',     1);
define('OAUTH2_ERROR_MISMATCHING_PARAMETER_REDIRECT_URI', 2);
define('OAUTH2_ERROR_INVALID_PARAMETER_REDIRECT_URI',     3);
define('OAUTH2_ERROR_MISSING_PARAMETER_CLIENT_ID',        4);
define('OAUTH2_ERROR_INVALID_PARAMETER_CLIENT_ID',        5);
define('OAUTH2_ERROR_UNEXPECTED_SITUATION',               6);

class oauth2_server_authorization_class
{
	public $error = '';
	public $exit = false;
	public $options;

	private $error_code = OAUTH2_ERROR_NONE;
	private $error_message = '';

	Function SetAuthorizationError($error_code, $error_message)
	{
		$this->options->OutputDebug('Authorization error '.$error_code.': '.$error_message);
		$this->error_code = $error_code;
		$this->error_message = $error_message;
		return true;
	}
	
	Function ValidateRedirectURI(&$redirect_uri)
	{
		if(!IsSet($_GET['redirect_uri']))
		{
			$redirect_uri = null;
			return $this->SetAuthorizationError(OAUTH2_ERROR_MISSING_PARAMETER_REDIRECT_URI, 'the redirect_uri parameter is missing');
		}
		$redirect_uri = $_GET['redirect_uri'];
		if(!$this->handler->CheckRedirectURI($redirect_uri, $valid, $match))
			return false;
		if(!$valid)
			return $this->SetAuthorizationError(OAUTH2_ERROR_INVALID_PARAMETER_REDIRECT_URI, 'the redirect_uri parameter is invalid');
		if(!$match)
			return $this->SetAuthorizationError(OAUTH2_ERROR_MISMATCHING_PARAMETER_REDIRECT_URI, 'the redirect_uri parameter does not match the accepted URI pattern');
		return true;
	}
	
	Function ValidateClientID(&$client_id)
	{
		if(!IsSet($_GET['client_id']))
		{
			$redirect_uri = null;
			return $this->SetAuthorizationError(OAUTH2_ERROR_MISSING_PARAMETER_CLIENT_ID, 'the client_id parameter is missing');
		}
		$client_id = $_GET['client_id'];
		if(!$this->handler->CheckClientID($client_id, $valid))
			return false;
		if(!$valid)
			return $this->SetAuthorizationError(OAUTH2_ERROR_INVALID_PARAMETER_CLIENT_ID, 'the client_id parameter is invalid');
		return true;
	}

	Function RedirectError($redirect_uri, $details, $state)
	{
		$redirect = $redirect_uri.'#';
		$first = true;
		foreach($details as $name => $value)
		{
			if($first)
				$first = false;
			else
				$redirect .= '&';
			$redirect .= $name.'='.$value;
		}
		Header('HTTP/1.0 302 Redirect');
		Header('Location: '.$redirect);
		$this->options->OutputDebug('Redirecting an error to: '.$redirect);
		return true;
	}

	Function RedirectClient($redirect_uri, $parameters)
	{
		$redirect = $redirect_uri;
		foreach($parameters as $name => $value)
			$redirect .= (strpos($redirect, '?') === false ? '?' : '&').$name.'='.UrlEncode($value);
		Header('HTTP/1.0 302 Redirect');
		Header('Location: '.$redirect);
		$this->options->OutputDebug('Redirecting a client to: '.$redirect);
		return true;
	}

	Function GenerateResponseCode($client_id, &$code)
	{
		do
		{
			$code = md5(uniqid(rand(), true));
			if(!$this->handler->CheckResponseCode($client_id, $code, $valid))
				return false;
		}
		while(!$valid);
		return true;
	}
	
	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the authorization class';
			return false;
		}
		$this->options->debug_prefix = 'OAuth server authorization: ';
		$this->options->LoadLocale('authorization');
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
		if(!$this->ValidateRedirectURI($redirect_uri))
			return false;
		if($this->error_code !== OAUTH2_ERROR_NONE)
			return true;
		$this->options->OutputDebug('The redirect URI is: '.$redirect_uri);
		$this->options->OutputDebug('Checking the response type...');
		$response_type = (IsSet($_GET['response_type']) ? $_GET['response_type'] : '');
		$state = (IsSet($_GET['state']) ? $_GET['state'] : null);
		switch($response_type)
		{
			case 'code':
				break;
			default:
				$details = array(
					'error'=>'unsupported_response_type',
					'error_description'=>'The provided value for the input parameter \'response_type\' is not valid. Expected values are the following: \'code\'',
				);
				return $this->RedirectError($redirect_uri, $details, $state);
		}
		$this->options->OutputDebug('The response type is: '.$response_type);
		$this->options->OutputDebug('Checking the client id...');
		if(!$this->ValidateClientID($client_id))
			return false;
		$this->options->OutputDebug('The client id is: '.$client_id);
		switch($response_type)
		{
			case 'code':
				$this->options->OutputDebug('Generating a response code...');
				if(!$this->GenerateResponseCode($client_id, $code))
					return false;
				$parameters = array('code'=>$code);
				if(IsSet($state))
					$parameters['state'] = $state;
				return $this->RedirectClient($redirect_uri, $parameters);
			default:
				return $this->SetAuthorizationError(OAUTH2_ERROR_UNEXPECTED_SITUATION, 'the authorization processs is not yet ready to handle responses of type '.$response_type);
		}
		return $this->SetAuthorizationError(OAUTH2_ERROR_UNEXPECTED_SITUATION, 'the authorization processs is not yet fully implemented');
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
		if($this->error_code !== OAUTH2_ERROR_NONE)
		{
			$message = str_replace(array(
					'{error_code}',
					'{error_message}'
				),
				array(
					$this->error_code,
					$this->error_message
				),
				$this->options->GetText('It was not finish the authorization process due to an error ({error_code}): {error_message}')
			);
			$page_template = new page_template_class;
			$page_template->options = $this->options;
			$page_template->title_prefix = '';
			$page_template->title = $this->options->GetHtmlText('Authorization error');
			$page_template->header();
			echo '<p>'.HtmlSpecialChars($message).'</p>';
			$page_template->footer();
		}
	}
};

?>