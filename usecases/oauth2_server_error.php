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
		$this->options->debug_prefix = 'OAuth server error: ';
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
		$message = str_replace('{error}', $this->error, $this->options->GetText('Error: {error}'));
		if($this->web)
		{
			$page_template = new page_template_class;
			$page_template->options = $this->options;
			$page_template->title_prefix = '';
			$page_template->title = $this->options->GetHtmlText('Application error');
			$page_template->header();
			echo '<p>'.HtmlSpecialChars($message).'</p>';
			$page_template->footer();
		}
		else
			echo $message, "\n";
	}
};

?>