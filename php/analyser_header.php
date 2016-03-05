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
	echo "<table><tr>";
	echo "<td><form action=\"activity_analyser.php\">
		<input type=\"submit\" value=\"Activity Analyser\">
		</form></td>";
	echo "<td><form action=\"guild_analyser.php\">
		<input type=\"submit\" value=\"Guild Analyser\">
		</form></td>";
	echo "<td><form action=\"map_score_analyser.php\">
		<input type=\"submit\" value=\"Map Score Analyser\">
		</form></td>";
	echo "<td><form action=\"logout.php\" method=\"GET\">
		<input type=\"submit\" value=\"Log out\"/>
	</form></td>";
	echo "</tr></table><br/>";
?>
<?php 
	function generate_option($value,$text,$select_name)
	{
		echo "<option value=\"" . $value . "\" ";
		if ($_GET[$select_name] == $value)
		{
			echo "selected=\"true\"";
		}
		echo ">" . $text . "</option>";
	}
	function check_inputs()
	{
		$pattern = '|()[]{}/\\\'\"+_;*&^%$#@!?<>,';
		foreach ($_GET as $param)
		{
			if (strpos($pattern,$param) !== false)
			{
				die("Invalid input");
			}
		}
	}
	?>
