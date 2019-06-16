<?php
/*
 * auth2_server_error.php
 *
 * @(#) $Id: $
 *
 */
 
class oauth2_server_error_class
{
	public $error = '';
	public $exit = false;
	public $options;
	public $web = true;

	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the error class';
			return false;
		}
		return true;
	}

	Function Process()
	{
		return true;
	}
	
	Function Finalize($success)
	{
		return $success;
	}

	Function Output()
	{
		$message = 'Error: '.$this->error;
		if($this->web)
			echo '<p>'.HtmlSpecialChars($message).'</p>';
		else
			echo $message, "\n";
	}
};

?>