<?php

// This is the configuration for yiic console application.
// Any writable CConsoleApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'My Console Application',
	// application components
        
        'import'=>array(
		'application.models.*',
		'application.components.*',
                'application.extensions.*',
                'application.extensions.file-uploader.*',
                'application.extensions.simplehtmldom.*',
	),
        
	'components'=>array(
		'db'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=center',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => '123',
			'charset' => 'utf8',
		),
	),
);