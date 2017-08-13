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
    
        <form id='changeConfigForm'>

            <div class='form-group'>
                <label for='inputUserName'>Schulname</label>
                <input type='text' class='form-control' id='inputschoolName' name='schoolName' placeholder='Schulname' value="<?php echo(escape($config->getConfig('school')['name'])); ?>" >
            </div>
            
            <div class='form-group'>
                <label for='inputUserName'>Adresse</label>
                <input type='text' class='form-control' id='inputschoolStreet' name='schoolStreet' placeholder='Straße' value="<?php echo(escape($config->getConfig('school')['adress']['street'])); ?>" >
                <input type='text' class='form-control' id='inputschoolPostcode' name='schoolPostcode' placeholder='Postleitzahl' value="<?php echo(escape($config->getConfig('school')['adress']['postcode'])); ?>" >
                <input type='text' class='form-control' id='inputschoolState' name='schoolState' placeholder='Bundesland' value="<?php echo(escape($config->getConfig('school')['adress']['state'])); ?>" >
                <input type='text' class='form-control' id='inputschoolLand' name='schoolLand' placeholder='Land' value="<?php echo(escape($config->getConfig('school')['adress']['land'])); ?>" >
            </div>

            <div class='form-group'>
                <label for='inputUserName'>Telefonnummer</label>
                <input type='text' class='form-control' id='inputschoolPhonenumber' name='schoolPhonenumber' placeholder='Telefonnummer' value="<?php echo(escape($config->getConfig('school')['phonenumber'])); ?>" >
            </div>
            
            <div class='form-group'>
                <label for='inputUserName'>Faxnummer</label>
                <input type='text' class='form-control' id='inputschoolFaxnumber' name='schoolFaxnumber' placeholder='Faxnummer' value="<?php echo(escape($config->getConfig('school')['faxnumber'])); ?>" >
            </div>
            
            <div class='form-group'>
                <label for='inputUserName'>Internetadresse</label>
                <input type='text' class='form-control' id='inputschoolUrl' name='schoolUrl' placeholder='Internetadresse' value="<?php echo(escape($config->getConfig('school')['url'])); ?>" >
            </div>
            
            <div class='form-group'>
                <label for='inputTitle'>Title</label>
                <input type='text' class='form-control' id='inputTitle' name='title' placeholder='' value="<?php echo(escape($config->getConfig('title'))); ?>" >
            </div>
            
            <div class='form-group'>
                <label for='inputTitle'>Title Abkürzung</label>
                <input type='text' class='form-control' id='inputTitleAbbreviation' name='titleAbbreviation' placeholder='' value="<?php echo(escape($config->getConfig('titleAbbreviation'))); ?>" >
            </div>
            
            <button type='submit' class='btn btn-primary' id='btn-edit-config'>Speichern</button>
            
            <div id='changeConfigFormMessage'></div>
            
        </form>

<?php include_once 'inc/footer.php'; ?>

