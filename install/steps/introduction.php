<?php

	foreach ($introduction as $key => $value)
		$$key = $value;
		
?>
<h3>Einführung</h3>

<p>Sie sind dabei <b><?php  echo $product; ?></b> (Version: <?php echo $productVersion; ?>) zu installieren. Developed by <b><?php echo $company; ?></b>.</p>

<form method="post">
	<input type="hidden" name="nextStep" value="eula">
	<button type="submit" class="button positive">
		<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Start
	</button>
</form>