<?php
/*
 *
 * @(#) $Id: debug.php,v 1.2 2010/07/12 08:01:35 mlemos Exp $
 *
 */

class debug_template_class
{
	var $options;
	var $web = true;

	Function Debug($message)
	{
		if(strlen($message))
		{
			if($this->web)
			{
?><p><tt><?php echo nl2br(HtmlSpecialChars($message)); ?></tt></p><?php
			}
			else
				echo $message;
		}
	}

	Function Error($error)
	{
		if($this->web)
		{
?>
<br />
<p><b>Error</b>: <?php echo HtmlSpecialChars($error); ?></p>
<?php
		}
		else
			echo "\n", 'Error: ', $error, "\n";
	}

	Function ApplicationError($application_name)
	{
		if($this->web)
		{
?>
<p style="text-align: center"><b><?php
	echo str_replace('{application}', HtmlSpecialChars($application_name),
		str_replace('{break}', '<br />',
		$this->options->GetHtmlText('Sorry, for the time being{break}{application} is not available.')));
?></b></p>
<?php
		}
		else
			echo str_replace('{application}', $application_name,
				str_replace('{break}', "\n",
				$this->options->GetText('Sorry, for the time being{break}{application} is not available.'))), "\n";
	}
};

?>