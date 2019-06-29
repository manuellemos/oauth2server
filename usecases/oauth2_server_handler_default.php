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

	Function CheckResponseCode($client_id, $code, &$valid)
	{
		$valid = true;
		return true;
	}
};

?>