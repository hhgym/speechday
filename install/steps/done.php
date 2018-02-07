<?php
    include("helper.php");
	$furtherInstructions = file_get_contents("config/done.html");
	
?>

<h3>Done</h3>

<p>Die Installation ist (fast) fertig! <b>Der Installer wird nun den Ordner "install" löschen und Sie auf die Startseite weiterleiten.</b></p><br>
<p>Bitte kontrollieren Sie, ob der Ordner "install" auch entfernt worden ist.</p>

<hr>
<?php echo $furtherInstructions; ?>

	<form method="post">
		<input type="hidden" name="nextStep" value="afterinstall">
		<button type="submit" class="button positive">
			<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Abschließen
		</button>
	</form>