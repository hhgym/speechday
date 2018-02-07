<?php

	include("helper.php");
    
	$errors = array();
	$goToNextStep = false;

	$host = $configSpeechday->getConfig('database.host');
	$username = $configSpeechday->getConfig('database.username');
	$password = $configSpeechday->getConfig('database.password');
	$database = $configSpeechday->getConfig('database.name');

	// connect to db
	$con = mysqli_connect($host, $username, $password);
	mysqli_select_db($con, $database);
	
	// read import sql
	$import = file_get_contents("config/import.sql");
	
	$queries = array();
	PMA_splitSqlFile($queries, $import);
	
	foreach ($queries as $query)
	{
		if (!mysqli_query($con, $query['query']))
		{
			$errors[] = "<b>".mysqli_error($con)."</b><br>(".substr($query['query'], 0, 200)."...)";
		}
	}
   
   // insert admin account
   mysqli_query($con, 'INSERT INTO user (userName, passwordHash, firstName, lastName, class, role) VALUES ("admin", "$2y$10\$rxHdBYx/Lq2Od6etxBIj7OfMhVwEQpJn4bD.4tCAD/4g7VyTrPAum", "AdminVN", "AdminNN", "", "admin")');
   
	// close connection
	mysqli_close($con);
	
	// show error
?>
<h3>Importing SQL</h3>

<p>Wir bereiten nun die Datenbank vor.</p>
<hr>

<?php if (count($errors) > 0) { ?>
	<div class="error">Beim Importieren der Daten sind Fehler aufgetreten!</div>
	
	<ul>
		<?php foreach ($errors as $error): ?>
			<li><?php echo $error; ?></li>
		<?php endforeach; ?>
	</ul>
<?php } else { ?>
	<div class="success">Datenbank erfolgreich erstellt!</div>
<?php } ?>

<hr>

<a href="index.php" class="button negative">
	<img src="css/blueprint/plugins/buttons/icons/cross.png" alt=""/> Abbrechen
</a>

<?php if (count($errors) == 0) { ?>
	<form method="post">
		<input type="hidden" name="nextStep" value="done">
		<button type="submit" class="button positive">
			<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Weiter
		</button>
	</form>
<?php } else { ?>
	<form method="post">
		<input type="hidden" name="nextStep" value="importSQL">
		<button type="submit" class="button positive">
			<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Wiederholen
		</button>
	</form>
<?php } ?>