<?php
	include("helper.php");

	$goToNextStep = true;
	
	clearstatcache();
	
	$showPermissions = array();
	foreach ($filePermissions as $key => $value)
	{
		$error = "";
		$values = str_split($value);
		// $file = getRealpath(dirname(getenv('SCRIPT_FILENAME'))."/".$config['applicationPath'].$key);
		$file = $config['applicationPath']. '/' . $key;
        
		if (file_exists($file))
		{
			foreach ($values as $char)
			{
				switch ($char)
				{
					case "r": if (!is_readable($file)) $error = "Nicht lesbar"; break;
					case "w": if (!is_writable($file)) $error = "Nicht beschreibbar"; break;
					// funzt bei manchen servern nicht richtig...
					// case "x": if (!is_executable($file)) $error = "Not executeable"; break;
				}
			}
		}
		else
			$error = "Datei existiert nicht!";
		
		// combine string for user easy reading
		$showRequired = array();
		foreach ($values as $char)
		{
			switch ($char)
			{
				case "r": $showRequired[] = "Read"; break;
				case "w": $showRequired[] = "Write"; break;
				case "x": $showRequired[] = "Execute"; break; 
			}
		}
		
		$showPermissions[$key] = array("required" => $value, "error" => $error, "showRequired" => implode(", ", $showRequired), "realpath" => $file);	
		
		if ($error != "") $goToNextStep = false;
	}	
		
?>
<h3>File permissions</h3>

<?php if (!$goToNextStep) { ?>
	<div class="error">Der Installer hat unzureichende Dateirechte festgestellt! Beheben Sie die Fehler bevor Sie fortfahren.</div>
<?php } ?>

<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Real Path</th>
			<th>Required</th>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($showPermissions as $filename => $permissions): ?>
		<tr>
			<td><?php echo $filename; ?></td>
			<td><?php echo $permissions['realpath']; ?></td>
			<td><?php echo $permissions['showRequired']; ?></td>
			<td><?php if ($permissions['error'] == "") { ?><img src="img/icons/accept.png"> OK <?php } else { ?><img src="img/icons/cancel.png"><?php echo $permissions['error']; ?> <?php } ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<hr>


<a href="index.php" class="button negative">
	<img src="css/blueprint/plugins/buttons/icons/cross.png" alt=""/> Abbrechen
</a>

<?php if ($goToNextStep) { ?>
<form method="post">
	<input type="hidden" name="nextStep" value="configure">
	<button type="submit" class="button positive">
		<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Weiter
	</button>
</form>
<?php } else { ?>
    <?php if ($showPermissions["config/config.php"]['error'] != ""): ?>
        <form method="post">
            <input type="hidden" name="nextStep" value="filePermissions">
            <input type="hidden" name="createConfig" value="true">
            <button type="submit" class="button positive">
                <img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Config erstellen
            </button>
        </form>
    <?php endif; ?>
    
	<form method="post">
		<input type="hidden" name="nextStep" value="filePermissions">
		<button type="submit" class="button positive">
			<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Wiederholen
		</button>
	</form>
    
<?php } ?>
