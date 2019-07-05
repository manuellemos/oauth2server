<?php
/*
 * api.php
 *
 * @(#) $Id: $
 *
 */

	define('APPLICATION_PATH', '..');

	require(APPLICATION_PATH.'/vendor/autoload.php');

	$options = new oauth2_server_configuration_options_class;
	$options->application_path = APPLICATION_PATH;
	$options->initialize();

	if($options->maintenance)
		$case = new oauth2_server_maintenance_class;
	else
		$case = new oauth2_server_api_class;
	$case->options = $options;
	if(($success = $case->initialize()))
		$success = $case->finalize($case->process());
	if($case->exit)
		exit;
	if($success)
		$case->output();
	else
	{
		$error_case = new oauth2_server_error_class;
		$error_case->options = $options;
		$error_case->error = $case->error;
		$error_case->web = false;
		$error_case->api = true;
		if($error_case->initialize()
		&& $error_case->finalize($error_case->process()))
			$error_case->output();
	}
?>