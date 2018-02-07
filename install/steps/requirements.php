<?php
	$goToNextStep = true;

	// php version
	$currentPhpVersion = phpversion();
	$phpVersionOk = version_compare($currentPhpVersion, $requirements['phpVersion']) >= 0;
	if (!$phpVersionOk) $goToNextStep = false;
	
	// extensions
	$loadedExtensions = get_loaded_extensions();
	foreach ($loadedExtensions as $key => $ext) $loadedExtensions[$key] = strtolower($ext); 
	$showExtensions = array();
	
	foreach ($requirements['extensions'] as $ext)
	{
		$isLoaded = in_array($ext, $loadedExtensions);
		$showExtensions[$ext] =  $isLoaded;
		if (!$isLoaded) $goToNextStep = false;
	}
	
	// show requirements
	foreach ($requirements as $key => $value)
		$$key = $value;
		
?>
<h3>Server Anforderungen</h3>

<?php if (!$goToNextStep) { ?>
	<div class="error">Der Server erfüllt nicht Anforderungen. Beheben Sie die Fehler und aktualisieren Sie die Seite.</div>
<?php } ?>

<h4>PHP Version</h4>

<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Version</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Required</td>
			<td><?php echo $phpVersion; ?></td>
			<td></td>
		</tr>
		<tr>
			<td>Vorhanden</td>
			<td><?php echo $currentPhpVersion; ?></td>
			<td><?php if ($phpVersionOk) { ?> <img src="img/icons/accept.png"> OK <?php } else { ?> <img src="img/icons/cancel.png"> Bitte aktualisieren Sie die PHP Version!<?php } ?></td>
		</tr>
	</tbody>
</table>
<hr>

<h4>PHP Extensions</h4>

<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($showExtensions as $extension => $status): ?>
		<tr>
			<td><?php echo $extension; ?></td>
			<td><?php if ($status) { ?> <img src="img/icons/accept.png"> OK <?php } else { ?> <img src="img/icons/cancel.png"> Nicht installiert!<?php } ?> </td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<hr>


<a href="index.php" class="button negative">
	<img src="css/blueprint/plugins/buttons/icons/cross.png" alt=""/> Abbruch
</a>

<?php if ($goToNextStep) { ?>
	<form method="post">
		<input type="hidden" name="nextStep" value="filePermissions">
		<button type="submit" class="button positive">
			<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Weiter
		</button>
	</form>
<?php } else { ?>
	<form method="post">
		<input type="hidden" name="nextStep" value="requirements">
		<button type="submit" class="button positive">
			<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Wiederholen
		</button>
	</form>
<?php } ?>