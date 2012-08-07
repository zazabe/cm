<?php

define("IS_CRON", true);
define('DIR_ROOT', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once DIR_ROOT . 'library/CM/Bootloader.php';
CM_Bootloader::load(array('autoloader', 'constants', 'exceptionHandler', 'errorHandler', 'defaults'));

try {
	// Create class types and action verbs config PHP
	$fileHeader = '<?php' . PHP_EOL;
	$fileHeader .= '// This is autogenerated action verbs config file. You should not adjust changes manually.' . PHP_EOL;
	$fileHeader .= '// You should adjust TYPE constants and regenerate file using `' . basename(__FILE__) . '`' . PHP_EOL;
	$path = DIR_ROOT . 'config/internal.php';
	$classTypesConfig  = CM_App::getInstance()->generateConfigClassTypes();
	$actionVerbsConfig = CM_App::getInstance()->generateConfigActionVerbs();
	CM_File::create($path, $fileHeader . $classTypesConfig . PHP_EOL . PHP_EOL . $actionVerbsConfig);
	echo 'create  ' . $path . PHP_EOL;

	// Create model class types and action verbs config JS
	$path = DIR_ROOT . 'config/js/internal.js';
	$modelTypesConfig = 'cm.model.types = ' . CM_Params::encode(CM_App::getInstance()->getClassTypes('CM_Model_Abstract'), true) . ';';
	$actionVerbs = array();
	foreach (CM_App::getInstance()->getActionVerbs() as $verb) {
		$actionVerbs[$verb['name']] = $verb['value'];
	}
	$actionVerbsConfig = 'cm.action.verbs = ' . CM_Params::encode($actionVerbs, true) . ';';
	CM_File::create($path, $modelTypesConfig . PHP_EOL . $actionVerbsConfig);
	echo 'create  ' . $path . PHP_EOL;

} catch (Exception $e) {
	echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}