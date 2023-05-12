<?php
/*
 * oauth2_server_login_dialog.php
 *
 * @(#) $Id: $
 *
 */
 
class oauth2_server_login_dialog_class
{
	public $error = '';
	public $exit = false;
	public $options;
	public $debug = '';

	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the login dialog class';
			return false;
		}
		return true;
	}

	Function Process()
	{
		if(!IsSet($this->options->login_dialog))
		{
			$this->error = 'the login dialog class is not defined';
			return false;
		}
		$this->options->OutputDebug('Authenticating the current user...');
		$this->dialog = new $this->options->login_dialog;
		$this->dialog->options = $this->options;
		if(($success = $this->dialog->initialize()))
			$success = $this->dialog->finalize($this->dialog->process());
		$this->debug .= $this->dialog->debug;
		if($this->dialog->exit)
		{
			$this->exit - true;
			return true;
		}
		if(!$success)
		{
			$this->error = $this->dialog->error;
			return false;
		}
		$this->options->OutputDebug($this->dialog->logged_in ? 'The user is authenticated.' : 'The user could not be authenticated');
		if(!$this->dialog->logged_in)
		{
			return true;
		}
		return true;
	}
	
	Function Finalize($success)
	{
		return $success;
	}

	Function Output()
	{
		$this->dialog->Output();
	}
};

?>