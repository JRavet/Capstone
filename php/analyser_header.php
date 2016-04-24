<?php
	session_start();
	try
	{
		$conn = new PDO("mysql:host=localhost;dbname=Gw2Analyser","gw2analyser","themirrorimage");
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$userQuery="SELECT COUNT(*) FROM user WHERE name=\"" . $_SESSION["username"] . "\" AND password=password('". $_SESSION["password"] . "');";
		$user = $conn->query($userQuery);
		if ($user->fetchColumn() == 0)
		{ //check if the username/password combination exists in the database
			header("Location: logout.php?error=inv_cred"); //manually add a GET variable to tell the index-login page there was an error
		} //if it doesn't, return to the login page via logout (to remove session variables)
	}
	catch(PDOException $e)
	{
		header("Location: logout.php?error=db_err"); //manually add a GET variable to tell the index-login page there was an error
	}
	function createButton($pageName,$buttonText)
	{
		echo "<td><form action=\"" . $pageName . "\">
			<input type=\"submit\" value=\"" . $buttonText ."\">
		</form></td>";
	}
	echo "<center><h1>GW2 Competitive Analytic Tool</h1></center>";
	echo "<table><tr>";
	createButton("match_details_analyser.php","Match Detail Analyser");
	createButton("activity_analyser.php","Activity Analyser");
	createButton("activity_map.php","Activity Map");
	createButton("guild_analyser.php","Guild Analyser");
	createButton("map_score_table.php","Map Score Table");
	createButton("map_score_graph.php","Map Score Graph");
	createButton("logout.php","Log Out");
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
