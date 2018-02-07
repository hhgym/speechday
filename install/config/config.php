<?php
	$config['header'] = "Speechday Setup Wizard";
	$config['applicationPath'] = dirname(dirname(__DIR__));
	// $config['database_file'] = "install/config/database.php";
	
	// INTRODUCTION
	$introduction = array();
	$introduction["product"] = "Speechday";
	$introduction["productVersion"] = "1.0.0";
	$introduction["company"] = "Heinrich-Hertz-Gymnasium Berlin";

	// SERVER REQUIREMENTS
	$requirements = array();
	$requirements["phpVersion"] = "5";
	$requirements["extensions"] = array("mysqli", "pdo", "curl");

	// FILE PERMISSIONS
	// r = readable, w = writable, x = executable
    // relativ to $config['applicationPath']
    // no trailing slash
	$filePermissions = array();
	$filePermissions["uploads"] = "rw";
	$filePermissions["config/config.php"] = "r";