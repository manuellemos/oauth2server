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
	
	Function CheckRedirectURI($redirect_uri, &$valid, &$match)
	{
		$valid = true;
		$match = true;
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
		if(!$this->CheckRedirectURI($redirect_uri, $valid, $match))
			return false;
		if(!$valid)
			return $this->SetAuthorizationError(OAUTH2_ERROR_INVALID_PARAMETER_REDIRECT_URI, 'the redirect_uri parameter is invalid');
		if(!$match)
			return $this->SetAuthorizationError(OAUTH2_ERROR_MISMATCHING_PARAMETER_REDIRECT_URI, 'the redirect_uri parameter does not match the accepted URI pattern');
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
	
	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the authorization class';
			return false;
		}
		$this->options->debug_prefix = 'OAuth server authorization: ';
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
		return $this->SetAuthorizationError(OAUTH2_ERROR_UNEXPECTED_SITUATION, 'the authorization processs is not yet fully implemented');
	}
	
	Function Finalize($success)
	{
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