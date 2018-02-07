<?php
    ob_start();
	session_start();
	require("config/config.php");
	
    require($config['applicationPath'] . '/code/dao/ConfigDAO.php');
    $configSpeechday = new Config($config['applicationPath'] . '/config/');
    
    if ($configSpeechday->getConfig('installed')) {
        //redirect
        header('Location: /');
        exit;
    }
    
	// show current step
	$nextStep = "introduction";
	if (isset($_POST['nextStep']))
		$nextStep = $_POST['nextStep'];
	
	
	// define vars
	$step = $nextStep;
	$header = $config['header'];
	$product = $introduction["product"];
	
    if (isset($_POST['createConfig']))
        if (!copy($config['applicationPath'].'/'.'config/config.php.dist', $config['applicationPath'].'/'.'config/config.php')) {
            echo "failed to copy";
        }
    
	include("inc/header.php");
	include("steps/".$nextStep.".php");
	include("inc/footer.php");
	