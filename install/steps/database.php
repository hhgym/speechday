<?php

	$error = false;
	$goToNextStep = false;
	
	if (isset($_POST['database']))
	{
		$database = $_POST['database'];
		$username = $_POST['username'];
		$password = $_POST['password'];
		$host = $_POST['host'];
		
		// check connection
		$connection = @mysqli_connect($host, $username, $password);
		if ($connection)
		{
			$error = !mysqli_select_db($connection, $database);
			@mysqli_close($connestion);
			
			if (!$error)
			{
				// save settings in database config file
				// load template
				$template = file_get_contents("config/database_template.php");
				$template = str_replace("%%host%%", $host, $template);
				$template = str_replace("%%username%%", $username, $template);
				$template = str_replace("%%password%%", $password, $template);
				$template = str_replace("%%database%%", $database, $template);
				
				// write config file
				$dbFile = $config['applicationPath'].'/'.$config['database_file'];
				file_put_contents($dbFile, $template);
				
				// save login in session for further use
				$_SESSION['db_host'] = $host;
				$_SESSION['db_user'] = $username;
				$_SESSION['db_pass'] = $password;
				$_SESSION['db_name'] = $database;
				
				// allow user to proceed
				$goToNextStep = true;
			}
			else $error = mysqli_error($connection);
		}
		else
			$error = mysqli_error($connection);
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
<h3>Database connection</h3>

<p>
	We need some information on the database. In all likelihood, these items were supplied to you by your Web Host. If you do not have this information, then you will need to contact them before you can continue.<br><br>
	Below you should enter your database connection details.
</p>
<hr>

<?php if ($error) { ?>
	<div class="error">
		<b>Error establishing a database connection: <?php echo $error; ?></b><br><br>
		This either means that the username and password information is incorrect or we can't contact the database server at <?php echo $host; ?>. Maybe your host's database server is down.<br><br>
		
		<ul>
			<li>Are you sure you have the correct username and password?</li>
    		<li>Are you sure that you have typed the correct hostname?</li>
    		<li>Are you sure that the database server is running?</li>
		</ul>
		
		If you're unsure what these terms mean you should probably contact your host. 
	</div>
<?php } ?>

<form method="post">
	<p>
		<label>Database name </label> (The name of the database you want to run this script in)<br>
		<input class="title" type="text" name="database" value="<?php echo $database; ?>">
	</p>
	<p>
		<label>Username</label> (Your MySQL username)<br>
		<input class="title" type="text" name="username" value="<?php echo $username; ?>">
	</p>
	<p>
		<label>Password</label> (...and MySQL password)<br>
		<input class="title" type="password" name="password" value="<?php echo $password; ?>">
	</p>
	<p>
		<label>Host</label> (You should be able to get this info from your web host, if "localhost" does not work.)<br>
		<input class="title" type="text" name="host" value="<?php echo $host; ?>">
	</p>
	
	<hr>
	
	<?php if ($goToNextStep) { ?>
		<div class="success">Everything is ok! Go to next step...</div>

		<a href="index.php" class="button negative">
			<img src="css/blueprint/plugins/buttons/icons/cross.png" alt=""/> Cancel
		</a>		
		
		<input type="hidden" name="nextStep" value="importSQL">
		<button type="submit" class="button positive">
			<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Next
		</button>
	<?php } else { ?>
		<a href="index.php" class="button negative">
			<img src="css/blueprint/plugins/buttons/icons/cross.png" alt=""/> Cancel
		</a>
		
		<input type="hidden" name="nextStep" value="database">
		<button type="submit" class="button positive">
			<img src="css/blueprint/plugins/buttons/icons/tick.png" alt=""/> Test connection
		</button>
	<?php } ?>
</form>