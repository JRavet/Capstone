<?php
	session_start();
?>
<html>
	<title>GW2 Analyser Log In</title>
	<form action="forward_to_analyser.php" method="POST">
		<table>
			<tr><td>User name:</td><td><input type="text" name="username"></td></tr>
			<tr><td>Password:</td><td><input type="password" name="password"></td></tr>
			<tr><td>Analyser:</td><td>
			<select name="analyser_type">
				<option value="activity_analyser.php">Activity Analyser</option>
				<option value="map_score_analyser.php">Map-score Analyser</option>
				<option value="guild_analyser.php">Guild Analyser</option>
			</select>
			</td></tr>
			<tr><td></td><td><input type="submit" value="Log In"></td></tr>
		</table>
		<?php
			if ($_GET["error"] == "inv_cred")
			{
				echo "<p>Error! Incorrect username or password!</p>";
			}
			else if ($_GET["error"] == "not_avail")
			{
				echo "<p>Error! That analyser is not yet available.</p>";
			}
		?>
	</form>
</html>
