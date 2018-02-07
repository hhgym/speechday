<?php
	$eula = file_get_contents("config/eula.txt");
	
?>
<h3>EULA</h3>

<div class="info">Bitte bestätigen Sie die EULA.</div>

<textarea style="height: 300px; width: 98%;"><?php echo $eula; ?></textarea>
<hr>

<a href="index.php" class="button negative">
	<img src="css/blueprint/plugins/buttons/icons/cross.png" alt=""/> Abbruch
</a>

<form method="post">
	<input type="hidden" name="nextStep" value="requirements">
	<button type="submit" class="button positive">
		<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Ich akzeptiere die "EULA"
	</button>
</form>