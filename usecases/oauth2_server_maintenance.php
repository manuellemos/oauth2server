<?php
/*
 * auth2_server_maintenance.php
 *
 * @(#) $Id: $
 *
 */
 
class oauth2_server_maintenance_class
{
	public $error = '';
	public $exit = false;
	public $options;
	public $web = true;

	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the maintenance class';
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
		$message = 'Sorry the '.$this->options->application_name.' is not available due to maintenance work. Please return later.';
		if($this->web)
			echo '<p>'.HtmlSpecialChars($message).'</p>';
		else
			echo $message, "\n";
	}
};

?>