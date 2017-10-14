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
    
        <form id='changeConfigForm' style="width:70%; margin-left: auto; margin-right: auto;">

        <h3>Schule</h3>
        
            <div class='form-group'>
                <label for='inputUserName'>Schulname</label>
                <input type='text' class='form-control' id='inputschoolName' name='schoolName' placeholder='Schulname' value="<?php echo(escape($config->getConfig('school')['name'])); ?>" >
            </div>
            
            <div class='form-group'>
                <label for='inputschoolAdress'>Adresse</label>
                    <div id='inputschoolAdress'>
                
                        <div>
                            <input type='text' class='form-control' id='inputschoolStreet' name='schoolStreet' placeholder='Straße mit Hausnummer' value="<?php echo(escape($config->getConfig('school')['adress']['street'])); ?>" >
                            <label for="inputschoolPostcode" class='below'>Straße mit Hausnummer</label>
                        </div>
                        
                        <div class="column">
                            <input type='text' class='form-control large' id='inputschoolPostcode' name='schoolPostcode' placeholder='Postleitzahl' value="<?php echo(escape($config->getConfig('school')['adress']['postcode'])); ?>" >
                            <label for="inputschoolPostcode" class='below'>Postleitzahl</label>
                        </div>
                        
                        <div class="column">
                            <input type='text' class='form-control large' id='inputschoolCity' name='schoolCity' placeholder='Stadt' value="<?php echo(escape($config->getConfig('school')['adress']['city'])); ?>" >
                            <label for="inputschoolState" class='below'>Stadt</label>
                        </div>
                        
                        <div class="column">
                            <input type='text' class='form-control large' id='inputschoolState' name='schoolState' placeholder='Bundesland' value="<?php echo(escape($config->getConfig('school')['adress']['state'])); ?>" >
                            <label for="inputschoolState" class='below'>Bundesland</label>
                        </div>
                        
                        <div>
                            <?php
                                $lands = array(
                                    '' => '',
                                    'Deutschland' => 'Deutschland',
                                    'Österreich' => 'Österreich',
                                    'Schweiz' => 'Schweiz',
                                );
                            ?>
                            <select class="form-control medium" id='selectschoolLand' name='schoolLand' value="<?php echo(escape($config->getConfig('school')['adress']['land'])); ?>"> 
                                

                                <?php foreach( $lands as $var => $land ): ?>
                                    <option value="<?php echo escape($var) ?>"<?php if( $var == $config->getConfig('school.adress.land') ): ?> selected="selected"<?php endif; ?>><?php echo escape($land) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="selectschoolLand" class='below'>Land</label>
                        </div>

                    </div>
            </div>

            <div class='form-group'>
                <label for='inputschoolPhonenumber'>Telefonnummer</label>
                <input type='text' class='form-control' id='inputschoolPhonenumber' name='schoolPhonenumber' placeholder='Telefonnummer' value="<?php echo(escape($config->getConfig('school')['phonenumber'])); ?>" >
            </div>
            
            <div class='form-group'>
                <label for='inputschoolFaxnumber'>Faxnummer</label>
                <input type='text' class='form-control' id='inputschoolFaxnumber' name='schoolFaxnumber' placeholder='Faxnummer' value="<?php echo(escape($config->getConfig('school')['faxnumber'])); ?>" >
            </div>
            
            <div class='form-group'>
                <label for='inputschoolUrl'>Internetadresse</label>
                <input type='text' class='form-control' id='inputschoolUrl' name='schoolUrl' placeholder='Internetadresse' value="<?php echo(escape($config->getConfig('school')['url'])); ?>" >
            </div>
            
            <div class='form-group'>
                <label for='inputschoolEmail'>Emailadresse</label>
                <input type='text' class='form-control' id='inputschoolEmail' name='schoolEmail' placeholder='Internetadresse' value="<?php echo(escape($config->getConfig('school')['email'])); ?>" >
            </div>
            
        <h3>Speechday</h3>
            
            <div class='form-group'>
                <label for='inputTitle'>Title</label>
                <input type='text' class='form-control' id='inputTitle' name='title' placeholder='' value="<?php echo(escape($config->getConfig('title'))); ?>" >
            </div>
            
            <div class='form-group'>
                <label for='inputTitleAbbreviation'>Title Abkürzung</label>
                <input type='text' class='form-control' id='inputTitleAbbreviation' name='titleAbbreviation' placeholder='' value="<?php echo(escape($config->getConfig('titleAbbreviation'))); ?>" >
            </div>
        
        <h3>Imap Auth</h3>
            
            <div class='form-group'>
                <label for='inputImapServer'>Server</label>
                <input type='text' class='form-control' id='inputImapServer' name='imapServer' placeholder='' value="<?php echo(escape($config->getConfig('imap_auth')['server'])); ?>" >
            </div>
            
            <div class='form-group'>
                <label for='inputImapDomain'>Domain</label>
                <input type='text' class='form-control' id='inputImapDomain' name='imapDomain' placeholder='' value="<?php echo(escape($config->getConfig('imap_auth')['domain'])); ?>" >
            </div>
            
            <button type='submit' class='btn btn-primary' id='btn-edit-config'>Speichern</button>
            
            <div id='changeConfigFormMessage'></div>
            
        </form>

<?php include_once 'inc/footer.php'; ?>

