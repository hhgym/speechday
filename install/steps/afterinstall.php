<?php
ob_start(); // ensures anything dumped out will be caught

include("helper.php");

$installpath = $config['applicationPath'] . '/install/';

delete_folder($installpath);

$configSpeechday->setConfig('installed', true);
file_put_contents($config['applicationPath'] . '/config/config.php', "<?php return " . var_export($configSpeechday->getConfig(), true) . ";" );

// clear out the output buffer
while (ob_get_status()) 
{
    ob_end_clean();
}

//redirect
header('Location: /');
exit;

?>