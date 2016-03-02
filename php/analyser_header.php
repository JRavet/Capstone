<?php
	session_start();
	try
	{
		$conn = new PDO("mysql:host=localhost;dbname=Gw2Analyser", $_SESSION["username"], $_SESSION["password"]);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch(PDOException $e)
	{
		header("Location: logout.php?error=inv_cred"); //manually add a GET variable to tell the index-login page there was an error
	}
	echo "<table>";
	echo "<tr><form action=\"logout.php\" method=\"GET\">
		<input type=\"submit\" value=\"Log out\"/>
	</form>";
	echo "<tr><form action=\"activity_analyser.php\">
		<input type=\"submit\" value=\"Activity Analyser\">
		</form></tr>";
	echo "<tr><form action=\"guild_analyser.php\">
		<input type=\"submit\" value=\"Guild Analyser\">
		</form></tr>";
	echo "</table><br/>";
?>
