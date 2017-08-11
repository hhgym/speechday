<?php
require_once('code/AuthenticationManager.php');
require_once('code/ViewController.php');
AuthenticationManager::checkPrivilege('admin');

include_once 'inc/header.php';
?>

<script type='text/javascript' src='js/config.js'></script>
<script type='text/javascript' src='js/validation.min.js'></script>



<p id='pageName' hidden>Config</p>

<div class='container'>

    <h1>Konfiguration</h1>

<?php print_r($config->getConfig('database')['host']); ?>

<?php include_once 'inc/footer.php'; ?>

