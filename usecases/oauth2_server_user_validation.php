<?php
/*
 * auth2_server_user_validation.php
 *
 * @(#) $Id: $
 *
 */
 
class oauth2_server_user_validation_class
{
	public $error = '';
	public $exit = false;
	public $options;
	public $validation_error_code = 0;
	public $values = array();
	
	const user_validation_error_none = 0;
	const user_validation_error_missing_password = 1;
	const user_validation_error_password_below_the_minimum_length = 2;

	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the class '.__CLASS__;
			return false;
		}
		return true;
	}

	Function Process()
	{
		foreach($this->options->user_validation_rules as $rule => $details)
		{
			if(!IsSet($details['type']))
			{
				$this->error = 'the type of rule is missing for user validation rule '.$rule;
				return false;
			}
			$type = $details['type'];
			switch($type)
			{
				case 'password':
					if(!IsSet($this->values['password']))
					{
						$this->validation_error_code = $this::user_validation_error_missing_password;
						return true;
					}
					$password = $this->values['password'];
					if(IsSet($details['minimum-length']))
					{
						if(strlen($password) < $details['minimum-length'])
						{
							$this->validation_error_code = $this::user_validation_error_password_below_the_minimum_length;
							return true;
						}
					}
					$this->validation_error_code = this::user_validation_error_none;
					return true;

				default:
					$this->error = 'rule type '.$type.' is not yet implemented '.__FILE__.' '.__LINE__;
					return false;
			}
		}
		$this->error = __FUNCTION__.' of class '.__CLASS__.' is not yet implemented '.__FILE__.' '.__LINE__;
		return false;
	}
	
	Function Finalize($success)
	{
		return $success;
	}

	Function Output()
	{
	}
};

?>