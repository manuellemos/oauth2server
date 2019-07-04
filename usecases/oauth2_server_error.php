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
	public $api = false;

	Function Initialize()
	{
		if(!IsSet($this->options))
		{
			$this->error = 'the options object was not set in the error class';
			return false;
		}
		$this->options->debug_prefix = 'OAuth server error: ';
		$this->options->LoadLocale('error');
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
		$message = str_replace(array(
				'{error}',
				'{application}'
			),
			array(
				$this->error,
				$this->options->application_name, 
			),
			$this->options->GetText('{application} error: {error}'));
		if($this->web)
		{
			$page_template = new page_template_class;
			$page_template->options = $this->options;
			$page_template->title_prefix = '';
			$page_template->title = str_replace('{application}', $this->options->application_name, $this->options->GetHtmlText('{application} error'));
			$page_template->header();
			echo '<p>'.HtmlSpecialChars($message).'</p>';
			$page_template->footer();
		}
		elseif($this->api)
		{
			$output = new stdClass;
			$output->message = $message;
			Header('HTTP/1.1 400 Bad Request');
			Header('Content-Type: application/json;charset=UTF-8');
			echo json_encode($output);
		}
		else
			echo $message, "\n";
	}
};

?>