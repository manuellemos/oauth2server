<?php
/*
 * oauth2_server_authorization_dialog.php
 *
 * @(#) $Id: $
 *
 */
 
class oauth2_server_authorization_dialog_class
{
	public $error = '';
	public $exit = false;
	public $options;
	public $handler;
	public $authorized = false;

	private $authorized_parameter = 'authorized';

	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the authorization dialog class';
			return false;
		}
		return true;
	}

	Function Process()
	{
		$this->authorized = IsSet($_GET['authorized']);
		return true;
	}
	
	Function Finalize($success)
	{
		return $success;
	}

	Function Output()
	{
		if(!$this->authorized)
		{
			$authorize_url = $_SERVER['REQUEST_URI'];
			$authorize_url .= (strpos($authorize_url, '?') === false ? '?' : '&').$this->authorized_parameter;
			echo '<a href="'.HtmlSpecialChars($authorize_url).'">'.'Click here to authorize to access your personal details'.'</a>';
		}
	}
};

?>