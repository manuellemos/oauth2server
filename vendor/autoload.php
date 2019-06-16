<?php

/*
 * autoload.php
 *
 * @(#) $Id: $
 */

$__classmap = array();

require(__DIR__.DIRECTORY_SEPARATOR.'classmaps.php');

spl_autoload_register(function($class)
{
	global $__classmap;

	if(IsSet($__classmap[$class]))
	{
		$class_file = $__classmap[$class];
		if(GetType($class_file) === 'array')
		{
			if(IsSet($class_file['include']))
			{
				foreach($class_file['include'] as $include)
				{
					require_once $include;
				}
			}
			include $class_file['path'];
		}
		else
			include $class_file;
	}
}, false, true);

?>