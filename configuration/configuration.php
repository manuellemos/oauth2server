<?php
/*
 *
 * @(#) $Id: $
 *
 */

class configuration_options_class
{
	var $application_path = '';
	var $application_name = 'OAuth 2 Server';
	var $debug = false;
	var $debug_http = true;
	var $maintenance = false;

/*
	var $site_url = 'https://web.4duser.com/';
	var $contact_email = 'info@4duser.com';

	var $search_console_client_id = '';
	var $search_console_client_secret = '';
	var $google_site_verification = '';

	var $locale = 'en';
	var $supported_locales = array(
		'en'=> true,
		'pt'=> true
	);
	var $text = array();
	var $locale_contexts = array();
	var $web = true;
	var $library_base_directory = '';
*/

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

/*
	Function StartSession()
	{
		if(!$this->web)
		{
			trigger_error('Session can only be started on a Web access');
			return false;
		}
		if(!function_exists('session_start'))
		{
			trigger_error('Session variables are not accessible in this PHP environment');
			return false;
		}
		if(session_id() === ''
		&& !session_start())
		{
			$message = 'it was not possible to start the PHP session';
			$e = error_get_last();
			if(IsSet($e))
				$error.=": ".$e['message'];
			trigger_error($message);
			return false;
		}
		return true;
	}

	Function LoadLocale($context)
	{
		if(!IsSet($this->locale_contexts[$context]))
		{
			if($this->web
			&& !IsSet($_SESSION['locale'])
			&& $this->StartSession())
			{
				if(IsSet($_SESSION['locale']))
				{
					$this->locale = $_SESSION['locale'];
				}
				else
				{
					if(IsSet($_SERVER['HTTP_ACCEPT_LANGUAGE']))
					{
						$accept = preg_split("/[\s;]+/", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
						foreach($accept as $language)
						{
							$languages = preg_split("/[\s,]+/", $language);
							foreach($languages as $locale)
							{
								$locale = trim(strtolower(strtok($locale, '-')));
								if(substr($locale, 0, 2) === 'q=')
									continue;
								if(IsSet($this->supported_locales[$locale]))
								{
									$this->locale = $locale;
									break 2;
								}
							}
						}
					}
					$_SESSION['locale'] = $this->locale;
				}
			}
			if(strcmp($context, 'common')
			&& !IsSet($this->locale_contexts['common']))
				$this->LoadLocale('common');
			$text = array();
			$path = $this->application_path.'/configuration/locale/'.$this->locale.'/'.$context.'.php';
			if(file_exists($path))
			{
				include($path);
				if(count($text))
					$this->text += $text;
				$this->locale_contexts[$context] = $this->locale;
			}
			else
				trigger_error('Missing locale file: "'.$path.'"');
		}
	}

	Function LoadProfile($profile)
	{
		if(!IsSet($profile)
		|| !IsSet($profile->locale)
		|| !IsSet($this->supported_locales[$locale = strtok($profile->locale, '-')])
		|| $this->locale === $locale)
			return;
		if($this->StartSession())
		{
			$contexts = array_keys($this->locale_contexts);
			$this->text = $this->locale_contexts = array();
			$this->locale = $_SESSION['locale'] = $locale;
			foreach($contexts as $context)
				$this->LoadLocale($context);
		}
	}

	Function GetText($text)
	{
		if(IsSet($this->text[$text]))
			 return($this->text[$text]);
		if($this->debug)
			trigger_error('Missing locale text: "'.$text.'"');
		return($text);
	}

	Function GetTextLabel($text)
	{
		return(preg_replace('/(^.*)_([^_]+)_(.*)$/', '\\1<u>\\2</u>\\3', $this->GetHtmlText($text)));
	}

	Function GetTextAccessKey($text)
	{
		return(preg_replace('/^.*_([^_]+)_.*$/', '\\1', $this->GetText($text)));
	}

	Function GetTextArray($text)
	{
		if(IsSet($this->text[$text]))
			 return($this->text[$text]);
		if($this->debug)
			trigger_error('Missing locale text array: "'.$text.'"');
		return(array());
	}

	Function GetHTMLText($text)
	{
		return(HtmlSpecialChars($this->GetText($text)));
	}
*/
};

?>