<?php
	session_start();
?>
<html>
	<title>GW2 Analyser Log In</title>
	<style>body{background:#FFF;}</style>
	<form action="forward_to_analyser.php" method="POST">
		<center><h1>GW2 Competitive Analytic Tool</h1></center>
		<table>
			<tr><td>User name:</td><td><input type="text" name="username"></td></tr>
			<tr><td>Password:</td><td><input type="password" name="password"></td></tr>
			<tr><td>Analyser:</td><td>
			<select name="analyser_type">
				<option value="match_details_analyser.php">Match Details Analyser</option>
				<option value="activity_analyser.php">Activity Analyser</option>
				<option value="guild_analyser.php">Guild Analyser</option>
				<option value="map_score_table.php">Map-score Table</option>
				<option value="map_score_graph.php">Map-score Graphs</option>
			</select>
			</td></tr>
			<tr><td></td><td><input type="submit" value="Log In"></td></tr>
		</table>
	
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
</html>
