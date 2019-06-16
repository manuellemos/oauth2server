<?php
/*
 *
 * @(#) $Id: $
 *
 */

class configuration_options_class
{
	public $application_path = '';
	public $application_name = 'OAuth 2 Server';
	public $debug = false;
	public $debug_http = true;
	public $maintenance = false;
	public $debug_output = '';
	public $debug_prefix = 'OAuth server authorization: ';
	public $log_file_name = '';

	Function ErrorHandler($error, $message, $file, $line, $backtrace)
	{
		if($error & error_reporting())
		{
			$log=array();
			switch($error)
			{
				case E_ERROR:
				case E_USER_ERROR:
					$type='FATAL';
					break;
				case E_WARNING:
				case E_USER_WARNING:
					$type='WARNING';
					break;
				case E_NOTICE:
				case E_USER_NOTICE:
					$type='NOTICE';
					break;
				default:
					$type='Unknown error type ['.$error.']';
					break;
			}
			$log[]=str_repeat('_',75);
			$request_uri = GetEnv('REQUEST_URI');
			$log[]=$type.': '.$message.' in line '.$line.' of file '.$file.', PHP '.PHP_VERSION.' ('.PHP_OS.')'.(strlen($request_uri) ? ' '.$request_uri : '').((IsSet($_POST) && count($_POST)) ? ' POST='.serialize($_POST) : '').((IsSet($_GET) && count($_GET)) ? ' GET='.serialize($_GET) : '').((IsSet($_FILES) && count($_FILES)) ? ' FILES='.serialize($_FILES) : '');
			for($level=1;$level<count($backtrace);$level++)
			{
				$message = '';
				if(IsSet($backtrace[$level]['file']))
					$message.='File: '.$backtrace[$level]['file'].' Line: '.$backtrace[$level]['line'].' Function: ';
				if(IsSet($backtrace[$level]['class']))
					$message.='(class '.$backtrace[$level]['class'].') ';
				if(IsSet($backtrace[$level]['type']))
					$message.=$backtrace[$level]['type'].' ';
				$message.=$backtrace[$level]['function'].'(';
				if(IsSet($backtrace[$level]['args']))
				{
					for($argument=0;$argument<count($backtrace[$level]['args']);$argument++)
					{
						if($argument>0)
							$message.=', ';
						if(GetType($backtrace[$level]['args'][$argument])=='object')
							$message.='class '.get_class($backtrace[$level]['args'][$argument]);
						else
							$message.=serialize($backtrace[$level]['args'][$argument]);
					}
				}
				$message.=')';
				$log[]=$message;
			}
			error_log(implode("\n\t",$log));
		}
		if($error==E_ERROR)
			exit(1);
	}

	Function CommonErrorHandler($error,$message,$file,$line)
	{
		$backtrace=(function_exists('debug_backtrace') ? debug_backtrace() : array());
		$this->ErrorHandler($error, $message, $file, $line, $backtrace);
	}

	Function FatalErrorHandler()
	{
		$error = error_get_last();
		if(IsSet($error))
			$this->ErrorHandler($error['type'], $error['message'], $error['file'], $error['line'], array());
	}

	Function Initialize()
	{
		ini_set('default_charset', 'UTF-8');
		set_error_handler(array(&$this, 'CommonErrorHandler'));
		register_shutdown_function(array(&$this, "FatalErrorHandler"));
		$local_options=$this->application_path.'/configuration/local_options.php';
		if(strlen($this->application_path)
		&& file_exists($local_options))
			require($local_options);
		return true;
	}

	Function Log($log, $action, $values)
	{
		$values['TIME'] = gmDate('H:i:s');
		$request_uri = GetEnv('REQUEST_URI');
		if(strlen($request_uri))
			$values['REQUEST_URI'] = $request_uri;
		$log_text = $action.':';
		foreach($values as $key => $value)
		{
			$log_text .= ' '.$key.'=';
			switch(GetType($value))
			{
				case 'string':
					$log_text .= '"'.str_replace('"', '\\"', str_replace('\\', '\\\\', $value)).'"';
					break;
				case 'integer':
				case 'double':
					$log_text .= $value;
					break;
				case 'boolean':
					$log_text .= ($value ? 'true' : 'false');
					break;
				default:
					$log_text .= serialize($value);
					break;
			}
		}
		if(($l = @fopen($this->application_path.'/var/logs/'.$log.'-'.gmDate('Y-m-d').'.log', 'a')))
		{
			fputs($l, $log_text."\n");
			fclose($l);
		}
		return $log_text;
	}
	
	Function OutputDebug($message)
	{
		if($this->debug)
		{
			$message = $this->debug_prefix.$message;
			$this->debug_output .= $message."\n";
			if(strlen($this->log_file_name))
				error_log($message."\n", 3, $this->log_file_name);
			else
				error_log($message);
		}
		return(true);
	}
};

?>