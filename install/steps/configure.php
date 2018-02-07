<?php

    function escape($string) {
        return nl2br(htmlspecialchars($string));
    }

	$error = false;
	$goToNextStep = false;
    $WriteConfigSuccess = true;
    
	if (isset($_POST['databaseName']))
	{
        
        $configSpeechday->setConfig('database.host', $_REQUEST['databaseHost']);
        $configSpeechday->setConfig('database.name', $_REQUEST['databaseName']);
        $configSpeechday->setConfig('database.username', $_REQUEST['databaseUsername']);
        $configSpeechday->setConfig('database.password', $_REQUEST['databasePassword']);
        $configSpeechday->setConfig('database.prefix', '');
        $configSpeechday->setConfig('database.extension', 'mysqli');
        
        $configSpeechday->setConfig('school.name', $_REQUEST['schoolName']);
        $configSpeechday->setConfig('school.adress.street', $_REQUEST['schoolStreet']);
        $configSpeechday->setConfig('school.adress.postcode', $_REQUEST['schoolPostcode']);
        $configSpeechday->setConfig('school.adress.city', $_REQUEST['schoolCity']);
        $configSpeechday->setConfig('school.adress.state', $_REQUEST['schoolState']);
        $configSpeechday->setConfig('school.adress.land', $_REQUEST['schoolLand']);
        $configSpeechday->setConfig('school.phonenumber', $_REQUEST['schoolPhonenumber']);
        $configSpeechday->setConfig('school.faxnumber', $_REQUEST['schoolFaxnumber']);
        $configSpeechday->setConfig('school.url', $_REQUEST['schoolUrl']);
        $configSpeechday->setConfig('school.email', $_REQUEST['schoolEmail']);
        
        $configSpeechday->setConfig('title', $_REQUEST['title']);
        $configSpeechday->setConfig('titleAbbreviation', $_REQUEST['titleAbbreviation']);
        
        $configSpeechday->setConfig('imap_auth.server', $_REQUEST['imapServer']);
        $configSpeechday->setConfig('imap_auth.domain', $_REQUEST['imapDomain']);
        
        $configSpeechday->setConfig('uploaddirectory', $config['applicationPath'] . '/code/dao/ConfigDAO.php');
        $configSpeechday->setConfig('version', $config['version']);
        $configSpeechday->setConfig('imap_auth.domain', $_REQUEST['imapDomain']);
        
        $WriteConfigSuccess = file_put_contents($config['applicationPath'] . '/config/config.php', "<?php return " . var_export($configSpeechday->getConfig(), true) . ";" );
        
		$database = $_POST['databaseName'];
		$username = $_POST['databaseUsername'];
		$password = $_POST['databasePassword'];
		$host = $_POST['databaseHost'];
		
		// check connection
		$connection = mysqli_connect($host, $username, $password, $database);
		if ($connection)
		{
			$goToNextStep = true;
            mysqli_close($connection);
		}
		else
			$error = mysqli_connect_errno();
	}
	else
	{
		if (isset($_SESSION['db_host']))
		{
			$host = $_SESSION['db_host'];
			$username = $_SESSION['db_user'];
			$password = $_SESSION['db_pass'];
			$database = $_SESSION['db_name'];
		}
		else
		{
			$database = "";
			$username = "";
			$password = "";
			$host = "localhost";
		}
	}
		
?>
<h3>Konfiguration von Speechday</h3>
<p>
Die Datenbank-Einstellung und ein Schulname sind notwendig. Alle anderen Einstellungen sind optional und können mit dem Admin Zugang direkt in <?php echo($product); ?> eingestellt werden.<br>
</p>

<?php if ($error): ?>
<div class="error">Keine Verbindung zur Datenbank. Bitte kontrollieren Sie Ihre Angaben.</div>
<?php endif; ?>
        
<?php if ($goToNextStep) : ?>
    <div class="success">Alles ok! Gehen Sie zum nächstem Schritt...</div>	
<?php endif; ?>
    
<form method="post">

    <div>
        <h4>Datenbank</h4>
        <p>
            <label>Datenbankname</label><br>
            <input class="title" type="text" name="databaseName" value="<?php echo(escape($configSpeechday->getConfig('database')['name'])); ?>" required>
        </p>
        <p>
            <label>Username</label><br>
            <input class="title" type="text" name="databaseUsername" value="<?php echo(escape($configSpeechday->getConfig('database')['username'])); ?>" required>
        </p>
        <p>
            <label>Passwort</label><br>
            <input class="title" type="password" name="databasePassword" value="<?php echo(escape($configSpeechday->getConfig('database')['password'])); ?>" required>
        </p>
        <p>
            <label>Datenbankhost</label><br>
            <input class="title" type="text" name="databaseHost" value="<?php echo(escape($configSpeechday->getConfig('database')['host'])); ?>" required>
        </p>
    </div>
    <div>
        <h4>Schule</h4>
        <p>
            <label >Schulname</label><br>
            <input class='title' type='text'  id='inputschoolName' name='schoolName' placeholder='Schulname' value="<?php echo(escape($configSpeechday->getConfig('school')['name'])); ?>" required>
        </p>
    </div>
    <div>
        <h4>Adresse</h4>
               
        <p>
            <label for="inputschoolPostcode" class='text'>Straße mit Hausnummer</label><br>
            <input type='text' class='title' id='inputschoolStreet' name='schoolStreet' placeholder='Straße mit Hausnummer' value="<?php echo(escape($configSpeechday->getConfig('school')['adress']['street'])); ?>" >
        </p>
        
        <p>
            <label for="inputschoolPostcode" class='below'>Postleitzahl</label><br>
            <input type='text' class='title' id='inputschoolPostcode' name='schoolPostcode' placeholder='Postleitzahl' value="<?php echo(escape($configSpeechday->getConfig('school')['adress']['postcode'])); ?>" >
        </p>
        <p>
            <label for="inputschoolState" class='below'>Stadt</label><br>
            <input type='text' class='title' id='inputschoolCity' name='schoolCity' placeholder='Stadt' value="<?php echo(escape($configSpeechday->getConfig('school')['adress']['city'])); ?>" >
        </p>
        <p>
            <label for="inputschoolState" class='below'>Bundesland</label><br>
            <input type='text' class='title' id='inputschoolState' name='schoolState' placeholder='Bundesland' value="<?php echo(escape($configSpeechday->getConfig('school')['adress']['state'])); ?>" >
        </p>
            <?php
                $lands = array(
                    '' => '',
                    'Deutschland' => 'Deutschland',
                    'Österreich' => 'Österreich',
                    'Schweiz' => 'Schweiz',
                );
            ?>
        <p>
            <select class="title" id='selectschoolLand' name='schoolLand' value="<?php echo(escape($configSpeechday->getConfig('school')['adress']['land'])); ?>"> 
                <?php foreach( $lands as $var => $land ): ?>
                    <option value="<?php echo escape($var) ?>"<?php if( $var == $configSpeechday->getConfig('school.adress.land') ): ?> selected="selected"<?php endif; ?>><?php echo escape($land) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="selectschoolLand" class='below'>Land</label>
        </p>
    </div>
    <div>
        <h4>Kontakt</h4>
        <p>
            <label for='inputschoolPhonenumber'>Telefonnummer</label><br>
            <input type='text' class='title' id='inputschoolPhonenumber' name='schoolPhonenumber' placeholder='Telefonnummer' value="<?php echo(escape($configSpeechday->getConfig('school')['phonenumber'])); ?>" >
        </p>
        <p>
            <label for='inputschoolFaxnumber'>Faxnummer</label><br>
            <input type='text' class='title' id='inputschoolFaxnumber' name='schoolFaxnumber' placeholder='Faxnummer' value="<?php echo(escape($configSpeechday->getConfig('school')['faxnumber'])); ?>" >
        </p>
        <p>
            <label for='inputschoolUrl'>Internetadresse</label><br>
            <input type='text' class='title' id='inputschoolUrl' name='schoolUrl' placeholder='Internetadresse' value="<?php echo(escape($configSpeechday->getConfig('school')['url'])); ?>" >
        </p>
        <p>
            <label for='inputschoolEmail'>Emailadresse</label><br>
            <input type='text' class='title' id='inputschoolEmail' name='schoolEmail' placeholder='Internetadresse' value="<?php echo(escape($configSpeechday->getConfig('school')['email'])); ?>" >

        </p>
    </div>

    <div>
    <h4>Speechday</h4>
        <p>
            <label for='inputTitle'>Title</label><br>
            <input type='text' class='form-control' id='inputTitle' name='title' placeholder='' value="<?php echo(escape($configSpeechday->getConfig('title'))); ?>" >
        </p>
        <p>
            <label for='inputTitleAbbreviation'>Title Abkürzung</label><br>
            <input type='text' class='title' id='inputTitleAbbreviation' name='titleAbbreviation' placeholder='' value="<?php echo(escape($configSpeechday->getConfig('titleAbbreviation'))); ?>" >
        </p>
	</div>
    <div>
    <h4>Imap Auth</h4>
    <p>
        <label for='inputImapServer'>Server</label><br>
                <input type='text' class='title' id='inputImapServer' name='imapServer' placeholder='' value="<?php echo(escape($configSpeechday->getConfig('imap_auth')['server'])); ?>" >
	</p>
    <p>
        <label for='inputImapDomain'>Domain</label><br>
        <input type='text' class='title' id='inputImapDomain' name='imapDomain' placeholder='' value="<?php echo(escape($configSpeechday->getConfig('imap_auth')['domain'])); ?>" >
	</p>
	</div>
	<hr>
	<?php if ($goToNextStep) { ?>
		<input type="hidden" name="nextStep" value="importSQL">
		<button type="submit" class="button positive">
			<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Weiter
		</button>
	<?php } else { ?>
        <?php if (!$WriteConfigSuccess): ?>
        <div class="error">Beim Speichern der Einstellung ist ein unbekannter Fehler aufgetreten!</div>
        <?php endif; ?>
        <a href="index.php" class="button negative">
            <img src="css/blueprint/plugins/buttons/icons/cross.png" alt=""/> Abbrechen
        </a>
        <input type="hidden" name="nextStep" value="configure">
        <button type="submit" class="button">
			Speichern
		</button>
	<?php } ?>
</form>
