<?php
/*
 * auth2_server_authorization.php
 *
 * @(#) $Id: $
 *
 */
 
class oauth2_server_authorization_class
{
	public $error = '';
	public $exit = false;
	public $options;

	private $error_code = OAUTH2_ERROR_NONE;
	private $error_message = '';
	private $response_code;
	private $redirect_uri;
	private $state;
	private $dialog;

	Function SetAuthorizationError($error_code, $error_message)
	{
		$this->options->OutputDebug('Authorization error '.$error_code.': '.$error_message);
		$this->error_code = $error_code;
		$this->error_message = $error_message;
		return true;
	}

	Function ValidateRedirectURI($redirect_uri)
	{
		if(!$this->handler->ValidateRedirectURI($redirect_uri))
			return false;
		if($this->handler->error_code !== OAUTH2_ERROR_NONE)
			return $this->SetAuthorizationError($this->handler->error_code, $this->handler->error_message);
		return true;
	}
	
	Function ValidateClientID($client_id)
	{
		if(!$this->handler->ValidateClientID($client_id))
			return false;
		if($this->handler->error_code !== OAUTH2_ERROR_NONE)
			return $this->SetAuthorizationError($this->handler->error_code, $this->handler->error_message);
		return true;
	}

	Function RedirectError($redirect_uri, $details, $state)
	{
		$redirect = $redirect_uri;
		$first = strpos('?', $redirect) === false;
		foreach($details as $name => $value)
		{
			if($first)
			{
				$first = false;
				$redirect .= '?';
			}
			else
				$redirect .= '&';
			$redirect .= $name.'='.UrlEncode($value);
		}
		Header('HTTP/1.0 302 Redirect');
		Header('Location: '.$redirect);
		$this->options->OutputDebug('Redirecting an error to: '.$redirect);
		return true;
	}

	Function GenerateResponseCode($parameters)
	{
		return $this->handler->GenerateResponseCode($parameters, $this->response_code);
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
		if(!IsSet($this->options->server_handler))
		{
				$this->error = 'the options object does not define the server_handler class option';
				return false;
		}
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
		$this->redirect_uri = (IsSet($_GET['redirect_uri']) ? $_GET['redirect_uri'] : null);
		if(!$this->ValidateRedirectURI($this->redirect_uri))
			return false;
		if($this->error_code !== OAUTH2_ERROR_NONE)
			return true;
		$this->options->OutputDebug('The redirect URI is: '.$this->redirect_uri);
		$this->options->OutputDebug('Checking the client id...');
		$client_id = (IsSet($_GET['client_id']) ? $_GET['client_id'] : null);
		if(!$this->ValidateClientID($client_id))
			return false;
		if($this->error_code !== OAUTH2_ERROR_NONE)
			return true;
		$this->options->OutputDebug('The client id is: '.$client_id);
		$this->options->OutputDebug('Checking the response type...');
		$response_type = (IsSet($_GET['response_type']) ? $_GET['response_type'] : '');
		$this->state = (IsSet($_GET['state']) ? $_GET['state'] : null);
		switch($response_type)
		{
			case 'code':
				break;
			default:
				$this->error_code = (IsSet($_GET['response_type']) ? OAUTH2_ERROR_MISSING_RESPONSE_TYPE : OAUTH2_ERROR_INVALID_RESPONSE_TYPE);
				$details = array(
					'error'=>'unsupported_response_type',
					'error_description'=>IsSet($_GET['response_type']) ? 'The provided value for the input parameter \'response_type\' is not supported. Supported values are the following: \'code\'' : 'The input parameter \'response_type\' is missing in the request URL.',
				);
				return $this->RedirectError($this->redirect_uri, $details, $this->state);
		}
		$this->options->OutputDebug('The response type is: '.$response_type);
		$scope = IsSet($_GET['scope']) ? $_GET['scope'] : null;
		$code = null;
		if(IsSet($this->options->authorization_dialog))
		{
			$this->options->OutputDebug('Obtaining use authorization...');
			$this->dialog = new $this->options->authorization_dialog;
			$this->dialog->options = $this->options;
			$this->dialog->handler = $this->handler;
			$this->dialog->scope = $scope;
			if(($success = $this->dialog->initialize()))
				$success = $this->dialog->finalize($this->dialog->process());
			if($this->dialog->exit)
			{
				$this->exit - true;
				return true;
			}
			if(!$success)
			{
				$this->error = $this->dialog->error;
				return $this->SetAuthorizationError(OAUTH2_ERROR_UNEXPECTED_SITUATION, 'the authorization dialog failed to obtain the authorization code due to an internal error');
			}
			$this->options->OutputDebug($this->dialog->authorized ? 'The user authorized to access the API.' : 'The user did not yet authorize to access the API.');
			if(!$this->dialog->authorized)
			{
				return true;
			}
			if(IsSet($this->dialog->code))
			{
				$code = $this->dialog->code;
			}
		}
		switch($response_type)
		{
			case 'code':
				$parameters = array(
					'redirect_uri'=>$this->redirect_uri,
					'response_type'=>$response_type,
					'client_id'=>$client_id,
					'scope'=>$scope,
				);
				if(IsSet($code))
				{
					$this->options->OutputDebug('The dialog process returned the response code: '.$code);
					$this->response_code = $code;
					return true;
				}
				$this->options->OutputDebug('Generating a response code...');
				return $this->GenerateResponseCode($parameters);
			default:
				return $this->SetAuthorizationError(OAUTH2_ERROR_UNEXPECTED_SITUATION, 'the authorization process is not yet ready to handle responses of type '.$response_type);
		}
		return $this->SetAuthorizationError(OAUTH2_ERROR_UNEXPECTED_SITUATION, 'the authorization process is not yet fully implemented');
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
		if(IsSet($this->dialog)
		&& !$this->dialog->authorized)
		{
			$this->dialog->Output();
		}
		elseif($this->error_code === OAUTH2_ERROR_NONE)
		{
			$parameters = array('code'=>$this->response_code);
			if(IsSet($this->state))
				$parameters['state'] = $this->state;
			$redirect = $this->redirect_uri;
			foreach($parameters as $name => $value)
				$redirect .= (strpos($redirect, '?') === false ? '?' : '&').$name.'='.UrlEncode($value);
			$this->options->OutputDebug('Redirecting a client to: '.$redirect);
			Header('HTTP/1.0 302 Redirect');
			Header('Location: '.$redirect);
		}
		else
		{
			$message = str_replace(array(
					'{error_code}',
					'{error_message}'
				),
				array(
					$this->error_code,
					$this->error_message
				),
				$this->options->GetText('It was not finished the authorization process due to an error ({error_code}): {error_message}')
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