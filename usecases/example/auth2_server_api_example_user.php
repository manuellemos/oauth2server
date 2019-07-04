<?php
/*
 * auth2_server_api_example_user.php
 *
 * @(#) $Id: $
 *
 */
 
class oauth2_server_api_example_user_class
{
	public $error = '';
	public $exit = false;
	public $options;

	private $user;

	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the API example user class';
			return false;
		}
		$this->options->debug_prefix = 'OAuth server api example user: ';
		$this->handler = new $this->options->server_handler;
		$this->handler->options = $this->options;
		if(!$this->handler->Initialize())
		{
			$this->error = $this->handler->error;
			return false;
		}
		return true;
	}

	Function Call()
	{
		$this->user = new StdClass;
		$this->user->name = 'Some User Name';
		return true;
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
		return $this->user;
	}
};

?>