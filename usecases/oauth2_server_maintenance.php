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
		$this->options->debug_prefix = 'OAuth server maintenance: ';
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
		$message = str_replace('{application}', $this->options->application_name, $this->options->GetText('Sorry the {application} is not available due to maintenance work. Please return later.'));
		if($this->web)
		{
			$page_template = new page_template_class;
			$page_template->options = $this->options;
			$page_template->title_prefix = '';
			$page_template->title = $this->options->GetHtmlText('Authorization error');
			$page_template->header();
			echo '<p>'.HtmlSpecialChars($message).'</p>';
			$page_template->footer();
		}
		else
			echo $message, "\n";
	}
};

?>