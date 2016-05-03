<?php
	session_start();
?>
<html>
  	<?php include 'bootstrap_styling.php'; ?>
	<title>GW2 Analyser Log In</title>
	
	<style>body{background:#FFF;}</style>
	<div class="col-sm-8 col-sm-offset-1">
	<form action="forward_to_analyser.php" method="POST">
		<center><h1>GW2 Competitive Analytic Tool</h1></center>
		<table>
			<tr><td>User name: </td><td><input class="form-control" type="text" placeholder="Enter user name" name="username"></td></tr>
			<tr><td><p></td></tr>
			<tr><td>Password:</td><td><input class="form-control" type="password" placeholder="Enter password" name="password"></td></tr>
			<tr><td><p></td></tr>
			<tr><td></td><td><input type="submit" value="Log In"></td></tr>
		</table>
	</div>
		<?php
			if ($_GET["error"] == "inv_cred")
			{
				echo "<p>Incorrect username or password.</p>";
			}
			else if ($_GET["error"] == "session_closed")
			{
				echo "<p>The current session has ended. Please log in again.</p>";
			}
			else if ($_GET["error"] == "db_err")
			{
				echo "<p>There was an error connecting to the database.</p>";
			}
		?>
	</form>	
	<div class="col-sm-3 sidenav">
	<br><br><br><br>
		<p>Please consider donating to help pay for the server</p>
		<form action="https://www.everbutton.com/pay/765">
			<input type="submit" value="Donate"/>
		</form>
	</div>
</html>
