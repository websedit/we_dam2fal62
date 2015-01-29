<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "we_dam2fal62".
 *
 * Auto generated 25-09-2014 08:40
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'DAM2FAL 6.2',
	'description' => 'This extension migrates your Dam-data into FAL.',
	'category' => 'module',
	'version' => '1.0.4',
	'state' => 'beta',
	'uploadfolder' => false,
	'createDirs' => '',
	'clearcacheonload' => false,
	'author' => 'Daniel Hasse - websedit AG',
	'author_email' => 'extensions@websedit.de',
	'author_company' => 'websedit AG',
	'constraints' => 
	array (
		'depends' => 
		array (
			'extbase' => '6.2.0-6.2.99',
			'fluid' => '6.2.0-6.2.99',
			'typo3' => '6.2.5-6.2.99',
			'filemetadata' => '6.2.0-6.2.99',
		),
		'conflicts' => 
		array (
		),
		'suggests' => 
		array (
		),
	),
);

