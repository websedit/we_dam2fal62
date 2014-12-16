<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "we_dam2fal62".
 *
 * Auto generated 15-12-2014 14:49
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'DAM2FAL 6.2',
	'description' => 'This extension migrates your Dam-data into FAL.',
	'category' => 'module',
	'shy' => false,
	'version' => '1.0.3',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => NULL,
	'loadOrder' => NULL,
	'module' => NULL,
	'state' => 'beta',
	'uploadfolder' => false,
	'createDirs' => '',
	'modify_tables' => NULL,
	'clearcacheonload' => false,
	'lockType' => NULL,
	'author' => 'Daniel Hasse - websedit AG',
	'author_email' => 'extensions@websedit.de',
	'author_company' => 'websedit AG',
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' =>
	array (
		'depends' =>
		array (
		),
		'conflicts' =>
		array (
		),
		'suggests' =>
		array (
		),
	),
	'_md5_values_when_last_written' => 'a:102:{s:8:"Classes/";s:4:"d41d";s:19:"Classes/Controller/";s:4:"d41d";s:43:"Classes/Controller/DamfalfileController.php";s:4:"c720";s:15:"Classes/Domain/";s:4:"d41d";s:21:"Classes/Domain/Model/";s:4:"d41d";s:35:"Classes/Domain/Model/Damfalfile.php";s:4:"1557";s:26:"Classes/Domain/Repository/";s:4:"d41d";s:50:"Classes/Domain/Repository/DamfalfileRepository.php";s:4:"a793";s:22:"Classes/ServiceHelper/";s:4:"d41d";s:40:"Classes/ServiceHelper/BackendSession.php";s:4:"c5bd";s:40:"Classes/ServiceHelper/FileFolderRead.php";s:4:"b3cb";s:14:"Configuration/";s:4:"d41d";s:18:"Configuration/TCA/";s:4:"d41d";s:32:"Configuration/TCA/Damfalfile.php";s:4:"b05b";s:25:"Configuration/TypoScript/";s:4:"d41d";s:38:"Configuration/TypoScript/constants.txt";s:4:"5a2e";s:34:"Configuration/TypoScript/setup.txt";s:4:"3b6a";s:14:"Documentation/";s:4:"d41d";s:28:"Documentation/Administrator/";s:4:"d41d";s:37:"Documentation/Administrator/Index.rst";s:4:"bd7e";s:24:"Documentation/ChangeLog/";s:4:"d41d";s:33:"Documentation/ChangeLog/Index.rst";s:4:"22d7";s:28:"Documentation/Configuration/";s:4:"d41d";s:37:"Documentation/Configuration/Index.rst";s:4:"8644";s:21:"Documentation/Images/";s:4:"d41d";s:41:"Documentation/Images/AdministratorManual/";s:4:"d41d";s:61:"Documentation/Images/AdministratorManual/ExtensionManager.png";s:4:"8598";s:44:"Documentation/Images/IntroductionPackage.png";s:4:"1161";s:32:"Documentation/Images/UserManual/";s:4:"d41d";s:46:"Documentation/Images/UserManual/abbildung1.png";s:4:"4355";s:46:"Documentation/Images/UserManual/abbildung2.png";s:4:"32a3";s:46:"Documentation/Images/UserManual/abbildung3.png";s:4:"e855";s:46:"Documentation/Images/UserManual/abbildung4.png";s:4:"3871";s:46:"Documentation/Images/UserManual/abbildung5.png";s:4:"d553";s:46:"Documentation/Images/UserManual/abbildung6.png";s:4:"435b";s:26:"Documentation/Includes.txt";s:4:"6d5f";s:23:"Documentation/Index.rst";s:4:"5ca4";s:27:"Documentation/Introduction/";s:4:"d41d";s:36:"Documentation/Introduction/Index.rst";s:4:"70db";s:28:"Documentation/KnownProblems/";s:4:"d41d";s:37:"Documentation/KnownProblems/Index.rst";s:4:"95b4";s:24:"Documentation/Readme.rst";s:4:"269c";s:26:"Documentation/Settings.yml";s:4:"e630";s:19:"Documentation/User/";s:4:"d41d";s:28:"Documentation/User/Index.rst";s:4:"d889";s:12:"ext_icon.gif";s:4:"e2a9";s:17:"ext_localconf.php";s:4:"bc81";s:14:"ext_tables.php";s:4:"4b3d";s:14:"ext_tables.sql";s:4:"268e";s:5:"Logs/";s:4:"d41d";s:10:"Resources/";s:4:"d41d";s:18:"Resources/Private/";s:4:"d41d";s:27:"Resources/Private/.htaccess";s:4:"183e";s:26:"Resources/Private/Backend/";s:4:"d41d";s:34:"Resources/Private/Backend/Layouts/";s:4:"d41d";s:46:"Resources/Private/Backend/Layouts/Default.html";s:4:"b962";s:35:"Resources/Private/Backend/Partials/";s:4:"d41d";s:50:"Resources/Private/Backend/Partials/Properties.html";s:4:"bf3b";s:36:"Resources/Private/Backend/Templates/";s:4:"d41d";s:47:"Resources/Private/Backend/Templates/Damfalfile/";s:4:"d41d";s:56:"Resources/Private/Backend/Templates/Damfalfile/List.html";s:4:"a5ff";s:67:"Resources/Private/Backend/Templates/Damfalfile/ReferenceUpdate.html";s:4:"12aa";s:66:"Resources/Private/Backend/Templates/Damfalfile/UpdateCategory.html";s:4:"d41d";s:27:"Resources/Private/Language/";s:4:"d41d";s:43:"Resources/Private/Language/de.locallang.xlf";s:4:"d197";s:48:"Resources/Private/Language/de.locallang_mod1.xlf";s:4:"6bea";s:40:"Resources/Private/Language/locallang.xlf";s:4:"49e0";s:81:"Resources/Private/Language/locallang_csh_tx_wedam2fal_domain_model_damfalfile.xlf";s:4:"06bc";s:43:"Resources/Private/Language/locallang_db.xlf";s:4:"58e8";s:45:"Resources/Private/Language/locallang_mod1.xlf";s:4:"b1f3";s:34:"Resources/Private/Language_aaaalt/";s:4:"d41d";s:47:"Resources/Private/Language_aaaalt/locallang.xlf";s:4:"a9d6";s:90:"Resources/Private/Language_aaaalt/locallang_csh_tx_wedam2fal62_domain_model_damfalfile.xlf";s:4:"425f";s:50:"Resources/Private/Language_aaaalt/locallang_db.xlf";s:4:"a9d6";s:26:"Resources/Private/Layouts/";s:4:"d41d";s:38:"Resources/Private/Layouts/Default.html";s:4:"dece";s:32:"Resources/Private/Layouts_aaalt/";s:4:"d41d";s:44:"Resources/Private/Layouts_aaalt/Default.html";s:4:"b649";s:27:"Resources/Private/Partials/";s:4:"d41d";s:42:"Resources/Private/Partials/Properties.html";s:4:"785c";s:28:"Resources/Private/Templates/";s:4:"d41d";s:39:"Resources/Private/Templates/Damfalfile/";s:4:"d41d";s:48:"Resources/Private/Templates/Damfalfile/List.html";s:4:"a5ff";s:59:"Resources/Private/Templates/Damfalfile/ReferenceUpdate.html";s:4:"6e12";s:58:"Resources/Private/Templates/Damfalfile/UpdateCategory.html";s:4:"598d";s:35:"Resources/Private/Templates_aaaalt/";s:4:"d41d";s:46:"Resources/Private/Templates_aaaalt/Damfalfile/";s:4:"d41d";s:55:"Resources/Private/Templates_aaaalt/Damfalfile/List.html";s:4:"c8b8";s:17:"Resources/Public/";s:4:"d41d";s:21:"Resources/Public/css/";s:4:"d41d";s:35:"Resources/Public/css/we_dam2fal.css";s:4:"8088";s:23:"Resources/Public/Icons/";s:4:"d41d";s:35:"Resources/Public/Icons/relation.gif";s:4:"e615";s:65:"Resources/Public/Icons/tx_wedam2fal62_domain_model_damfalfile.gif";s:4:"905a";s:21:"Resources/Public/img/";s:4:"d41d";s:30:"Resources/Public/img/error.gif";s:4:"9a95";s:27:"Resources/Public/img/ok.gif";s:4:"d843";s:20:"Resources/Public/js/";s:4:"d41d";s:42:"Resources/Public/js/jquery.validate.min.js";s:4:"4558";s:42:"Resources/Public/js/jquery.validate.old.js";s:4:"b368";s:32:"Resources/Public/js/jquerybib.js";s:4:"533d";s:37:"Resources/Public/js/jqueryuicustom.js";s:4:"916b";}',
);

?>