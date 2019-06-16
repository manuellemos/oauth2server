<?php

/*
 *
 * @(#) $Id: $
 *
 */

class page_template_class
{
	var $options;
	var $title = '';
	var $title_prefix = '';
	var $head = '';
	var $load = '';
	var $unload = '';
	var $header = '';
	var $footer = '';
	var $template_footer = '';
	var $header_title = true;
	var $default_refresh_wait = 0;
	var $debug = '';
	var $robots = '';
	var $responsive = false;
	var $facebook_application = '';
	var $image = '';
	var $use_theme = true;
	var $track_analytics = false;
	var $favicon = true;
	var $type = '';
	var $url = '';
	var $menu = '';

	Function GetCSS()
	{
		if($this->use_theme
		&& strlen($this->options->theme))
			return
'<link rel="stylesheet" type="text/css" href="'.$this->options->site_url.'css/theme/'.$this->options->theme.'/styles.css">
';
		return(
'<style type="text/css">
body { color: black ; font-family: arial, helvetica, sans-serif; margin: 0px; padding: 0px }
.page_title { text-align: center; margin: 0px; padding: 1em }
.page_title_message { text-align: center; }
.page_sub_title { text-align: center; }
.framed { padding: 10px 15px; margin:5px 0; border-radius: 8px ; -moz-border-radius: 8px; -webkit-border-radius: 8px; }
.invalid { background-color: #ffcccc; }
.warning { background-color: #ffb366; }
</style>
');
	}

	Function GetRefresh($url, $time)
	{
		return '<script type="text/javascript"><!--'."\n".($time ? "setTimeout(function() { window.location.href='".$url."'; }, ".($time*1000).");" : "window.location.href='".$url."';")."\n// --></script>\n".'<meta http-equiv="refresh" content="'.$time.';url='.HtmlSpecialChars($url).'">'."\n";
	}

	Function Refresh($url, $time = null)
	{
		if(!IsSet($time))
			$time = $this->default_refresh_wait;
		$this->head .= $this->GetRefresh($url, $time);
	}

	Function Header()
	{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo $this->title, (strlen($this->title_prefix) && strlen($this->title)) ? ' - ' : '', $this->title_prefix; ?></title>
<?php
	if($this->favicon)
	{
		echo '<link rel="shortcut icon" href="', HtmlSpecialChars($this->options->site_url), 'favicon.ico">', "\n";
	}
?><meta charset="UTF-8" />
<?php
		if($this->responsive)
		{
			echo '<meta name="viewport" content="width=device-width, initial-scale=1">',"\n";
		}
		if(strlen($this->url))
		{
			echo '<link rel="canonical" href="'.HtmlSpecialChars($this->url).'">', "\n";
			echo '<meta property="og:url" content="'.HtmlSpecialChars($this->url).'">',"\n";
		}
		if(strlen($this->facebook_application))
		{
			echo '<meta property="og:title" content="'.$this->title.'">',"\n";
			echo '<meta property="fb:app_id" content="'.HtmlSpecialChars($this->facebook_application).'">',"\n";
		}
		if(strlen($this->type))
			echo '<meta property="og:type" content="'.HtmlSpecialChars($this->type).'">',"\n";
		if(strlen($this->image))
		{
			echo '<meta property="og:image" content="'.HtmlSpecialChars($this->image).'">',"\n";
		}
		echo $this->GetCSS();
		if(strlen($this->robots))
			echo '<meta name="robots" content="'.HtmlSpecialChars($this->robots).'">',"\n";
		if(strlen($this->options->google_site_verification))
			echo '<meta name="google-site-verification" content="'.$this->options->google_site_verification.'">', "\n";
		echo $this->head;
		if($this->track_analytics)
		{
?>
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', '<?php echo $this->options->google_analytics; ?>', 'auto');
  ga('send', 'pageview');

</script>
<?php
		}
?>
</head>
<body<?php
		if (strlen($this->load))
			echo ' onload="'.HtmlSpecialChars($this->load).'"';
		if (strlen($this->unload))
			echo ' onunload="'.HtmlSpecialChars($this->unload).'"';
?>>
<?php
		if($this->use_theme)
		{
			$divider = '{body}';
			if(($template = file_get_contents($this->options->application_path.'/templates/theme/'.$this->options->theme.'/template.html')) === false)
				$template = $divider;
			$template = str_replace('{site}', $this->options->site_url, $template);
			$menu = $this->menu;
			$body = strpos($template, $divider);
			$this->template_footer = str_replace(
				array(
					'{application_name}',
					'{email}',
				),
				array(
					HtmlSpecialChars($this->options->application_name),
					$this->options->contact_email,
				),
				substr($template, $body + strlen($divider)));
			$header = str_replace(
				array(
					'{title}',
					'{logo}',
					'{small-logo}',
					'{menu}',
					'{application_name}',
				),
				array(
					$this->title,
					'<img src="'.$this->options->site_url.'graphics/theme/'.$this->options->theme.'/logo.png" alt="{application_name}">',
					'<img src="'.$this->options->site_url.'graphics/theme/'.$this->options->theme.'/logo-small.png" alt="{application_name}">',
					$menu,
					HtmlSpecialChars($this->options->application_name),
				),
				substr($template, 0, $body));
			echo $header;
		}
		echo $this->header;
	}

	Function GetHeader()
	{
		ob_start();
		$this->Header();
		$header = ob_get_contents();
		ob_end_clean();
		return($header);
	}

	Function Footer()
	{
		echo $this->footer;
		if(strlen($this->debug))
		{
			$debug = new debug_template_class;
			$debug->Debug($this->debug);
		}
		echo $this->template_footer;
?>
</body>
</html>
<?php
	}

	Function GetFooter()
	{
		ob_start();
		$this->Footer();
		$footer = ob_get_contents();
		ob_end_clean();
		return($footer);
	}

	Function GetTitleMessage($message, $class = '')
	{
		return('<h2 style="text-align: center"'.(strlen($class) ? ' class="'.HtmlSpecialChars($class).'"' : '').'>'.$message.'</h2>'."\n");
	}

	Function TitleMessage($message)
	{
		echo $this->GetTitleMessage($message);
	}

	Function GetMessage($message, $center)
	{
		return('<p'.($center ? ' style="text-align: center"' : '').'>'.$message.'</p>'."\n");
	}

	Function Message($message, $center)
	{
		echo $this->GetMessage($message, $center);
	}
};

?>